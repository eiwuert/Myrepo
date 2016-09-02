<?php
require_once('document.action.php');
require_once('template.php');
//require_once('template_parser.1.php');
require_once('automode.1.php');
require_once('filter.manager.php');
require_once('part.php');
require_once('Template/Parser.php');
require_once('Template/ArrayTokenProvider.php');
require_once('Template/SpanDecorator.php');

/*
	Globally defining these rather than having them as class variables, since they're not logically tied
	to a specific class.
*/
define('CONTENT_TYPE_TEXT_PLAIN', 'text/plain');
define('CONTENT_TYPE_TEXT_HTML', 'text/html');
define('CONTENT_TYPE_APPLICATION_PDF', 'application/pdf');
define('CONTENT_TYPE_IMAGE_TIFF', 'image/tiff');
define('CONTENT_TYPE_TEXT_RTF','text/rtf');

/**
 * This class represents a document inside of Condor.
 *
 * @author Brian Feaver
 * @copyright Copyright 2006 The Selling Source, Inc
 */
class Document
{
	private $application_id;
	private $archive_id;
	private $sql;
	private $doc_action;
	private $mode;
	private $user_id;
	private $condor_api_auth;
	
	/**
	 * @var int
	 */
	private $send_priority;

	private $attached_documents;	// Array of attached documents (after they've been rendered)
	private $root_id;
	private $root_part;
	private $track_key;
	private $space_key;

	private $template;
	private $template_id;
	private $template_name;
	private $pages;

	private $content_type;
	private $uri;
	private $subject;
	private $unique_id;

	private $undefined_tokens;

	const TYPE_INCOMING = 'INCOMING';
	const TYPE_OUTGOING = 'OUTGOING';
	const CONTENT_TEXT_HTML = 'text/html';
	const CONTENT_APPLICATION_PDF = 'application/pdf';
	const TOKEN_IDENT = '%%%';

	/**
	 * Document constructor.
	 *
	 * @param object $sql
	 * @param string $db
	 * @param string $template_name
	 * @param int $application_id
	 */
	function __construct($sql, $user_id, $mode = 'LIVE', $template_name = NULL, $application_id = NULL)
	{
		$this->sql = $sql;
		$this->template = NULL;
		$this->template_id = NULL;
		$this->template_name = $template_name;
		$this->application_id = $application_id;
		$this->attached_documents = array();
		$this->subject = '';
		$this->mode = $mode;
		$this->user_id = $user_id;
		$this->track_key = '';
		$this->space_key = '';
		$this->unique_id = '';
		$this->pages = 0;
		$this->archive_id = NULL;
		$this->undefined_tokens = array();
		
		// Setup the Document Action handler
		$this->Document_Action();
	}
	

	/**
	 * Create a document from the part
	 *
	 * @param unknown_type $part_id
	 * @return unknown
	 */
	public function Create_From_Part($part_id)
	{
		$ret_val = FALSE;
		//first makesure that the part exists
		try 
		{
			$part = new Part($this->sql,$this->user_id,$this->doc_action,$part_id);
			$part->Load();
			$obj = $part->Get_Return_Object();
			if($obj->data != NULL && !empty($obj->data))
			{
				//So the part exists, lets insert a new document
				//with it as the root.
				$query = "
					INSERT INTO
						document
					SET
						date_created = NOW(),
						date_modified = NOW(),
						root_id = {$part_id},
						type = 'INCOMING',
						subject = 'None',
						user_id = {$this->user_id}
				";
				$this->sql->Query($query);
				$ret_val = $this->sql->Insert_Id();
				if(!is_numeric($ret_val) && $ret_val > 0)
				{
					$ret_val = FALSE;
				}
			}
			
		}
		catch (Exception $e)
		{
			return FALSE;
		}
				
		return $ret_val;
	}

	/**
	 * Returns and sets the Condor API Authentication credentials.
	 *
	 * @param string $api_auth
	 */
	public function Condor_API_Auth($api_auth = NULL)
	{
		if(!is_null($api_auth)) $this->condor_api_auth = $api_auth;
		return $this->condor_api_auth;
	}

	/**
	 * Sets up the template for this document.
	 *
	 * @param int $company_id
	 * @return boolean
	 */
	private function Get_Template($company_id)
	{
		$this->template = new Template($this->sql);
		$ret_val = $this->template->Load_Template_By_Name($this->template_name, $company_id);

		if($ret_val == FALSE)
		{
			$this->template = NULL;
		}

		return $ret_val;
	}

	/**
	 * Returns the template name this document uses.
	 *
	 * @return string
	 */
	public function Get_Template_Name()
	{
		$ret_val = $this->template_name;

		if($this->template != NULL)
		{
			$ret_val = $this->template->Get_Name();
		}

		return $ret_val;
	}
	/**
	 * Sets the number of pages in this document and stores it in the database
	 * @param int $pages
	 * @return boolean
	 */
	public function Set_Page_Count($pages)
	{
		$ret_val = FALSE;
		if(is_numeric($pages) && is_numeric($this->archive_id))
		{
			$this->pages = $pages;
		
			$query = "
				UPDATE
					document
				SET
					page_count = '$pages'
				WHERE
					document_id = {$this->archive_id}
				LIMIT 1";

			$result = $this->sql->Query($query);
			$ret_val = ($this->sql->Affected_Row_Count() == 1);
		}

		return $ret_val;
	}

	/**
	 * Logs an action as printed.
	 */
	 public function Printed()
	 {
	 	return ($this->doc_action->Log_Action('PRINTED',$this->archive_id,$this->user_id) > 0);
	 }
	 /**
	  * Returns the printed dates
	  */
	 public function Get_Printed()
	 {
	 	return $this->doc_action->Get_Actions('PRINTED',$this->archive_id);
	 }

	public function Get_Signed()
	{
		return $this->doc_action->Get_Actions('SIGNED', $this->archive_id);
	}

	/**
	 * Returns the number of pages in the document
	 * @return int 
	 */
	public function Get_Page_Count()
	{
		$ret_val = NULL;

		if(!is_numeric($this->pages))
		{
			if(is_numeric($this->archive_id))
			{
				$query = "
					SELECT 
						page_count
					FROM
						document
					WHERE 
						document_id = {$this->archive_id}
					LIMIT 1";

				$result = $this->sql->Query($query);
				$row = $result->Fetch_Object_Row();
				$ret_val = $row->Fetch_Object_Row();
				$ret_val = $ret_val->page_count;
			}
		}
		else
		{
			$ret_val = $this->pages;
		}
		return $ret_val;
	}

	 /**
	 * Saves the document and all its attachments. If archive ID is given, it is assumed
	 * that we are inserting parts of a root document.
	 *
	 * This process goes as follows:
	 * 1. Insert the document into the document table, get the document_id (archive_id)
	 * 2. Insert the document path into the part table, get the part_id
	 * 3. Update the part table with the filename path
	 * 4. Save the file using the new filename
	 * 5. Update the document table, setting the root_id to the part_id
	 *
	 * This is all done within the Part class now. We just make a call to save the
	 * root_part.
	 *
	 * @param string $type
	 * @param string $content_type
	 * @param string $file_format
	 * @param int $archive_id
	 * @return int The archive ID.
	 */
	public function Save($type, $content_type, $file_format, $archive_id = NULL)
	{
		// Insert initial document if we don't provide the archive ID
		if($archive_id == NULL)
			$this->Insert_Document($type); // This would set the archive ID
		else
			$this->archive_id = $archive_id;

		// Insert document part
		$this->root_id = $this->root_part->Save($this->archive_id, $file_format);

		$this->Update_Document_Root_Id();

		$ret_val = $this->archive_id;

		return $ret_val;
	}

	/**
	 * Loads the document parts.
	 *
	 * @param int $document_id
	 * @return boolean
	 */
	public function Load($document_id, $render_pdf = FALSE, $render_parts_as_pdf = TRUE)
	{
		// Cannot load a document with no document_id
		if (empty($document_id))
		{
			return FALSE;
		}

		$ret_val = FALSE;
		$security = new Security($this->mode);
		$agents = $security->Get_All_Company_Agents($this->Condor_API_Auth());

		// Load the root information along with the document information
		$query = "
			/* ".__METHOD__." */
			SELECT
				template.name,
				template.send_priority,
				document.root_id,
				document.subject,
				document.track_key,
				document.space_key,
				document.page_count,
				document.subject,
				document.unique_id,
				document.application_id,
				part.parent_id,
				part.content_type,
				part.uri,
				part.file_name,
				part.compression,
				part.encrypted
			FROM
				document
				LEFT JOIN template ON document.template_id = template.template_id
				LEFT JOIN part ON document.root_id = part.part_id
			WHERE
				document_id = $document_id
			AND
				document.user_id IN (".join(',',$agents).")
			";
		try
		{
			$result = $this->sql->Query($query);
		}
		catch(Exception $e)
		{
			return FALSE;
		}

		if(($row = $result->Fetch_Object_Row()))
		{
			$this->template_name = $row->name;
			$this->subject = $row->subject;
			$this->root_id = $row->root_id;
			$this->track_key = $row->track_key;
			$this->space_key = $row->space_key;
			$this->unique_id = $row->unique_id;
			$this->subject = $row->subject; 
			$this->archive_id = $document_id;
			$this->pages = $row->page_count;
			$this->application_id = $row->application_id;
			$this->send_priority = $row->send_priority;

			// Setup the root part
			$this->root_part = new Part(
				$this->sql,
				$this->user_id,
				$this->doc_action,
				$this->root_id,
				($row->parent_id != 'NULL') ? $row->parent_id : null,
				$row->content_type,
				$row->uri,
				$row->file_name,
				$row->compression
			);
			$this->root_part->Use_Encryption($row->encrypted);
			$this->root_part->Load_File();
			$this->root_part->Load_Children();
			

			// If we want to render it as a PDF
			if($render_pdf) $this->root_part->Render_As_PDF($this->mode, TRUE);
			$ret_val = TRUE;
		}

		/*
			Look for any other "root" parts. These will probably be other documents that we'll
			be attaching as PDF's to the root document. For now, this will only return objects
			if that is the case. Otherwise the only part in a document that won't have a parent_id
			should be the root itself.
		*/

		$query = "
			SELECT
				document_part.part_id, 
				part.content_type,
				part.encrypted
			FROM
				document
				JOIN document_part ON document.document_id = document_part.document_id
				JOIN part ON document_part.part_id = part.part_id
			WHERE
				document_part.document_id = $document_id
				AND part.part_id != document.root_id
				AND ISNULL(part.parent_id)";

		try
		{
			$result = $this->sql->Query($query);
		}
		catch(Exception $e)
		{
			exit($e->getMessage());
		}

		while(($row = $result->Fetch_Object_Row()))
		{
			$extra_part = new Part($this->sql, $this->user_id, $this->doc_action, $row->part_id);
			$extra_part->Use_Encryption($row->encrypted);
			$extra_part->Load();
			//Render as PDF if content_type = text/html
			if($render_parts_as_pdf && $row->content_type == CONTENT_TYPE_TEXT_HTML)
			{
				$extra_part->Render_As_PDF($this->mode, TRUE);
			}
			else
			{
				$extra_part->Load_File();
			}

			$this->attached_documents[] = $extra_part;
		}

		return $ret_val;
	}

	/**
	 * Retuns a stdClass object for this document and all its parts.
	 *
	 * @return object
	 */
	public function Get_Return_Object()
	{
		if($this->root_part instanceof Part)
		{
			$return_obj = $this->root_part->Get_Return_Object();
			$return_obj->template_name = $this->template_name;
			$return_obj->unique_id = $this->unique_id;
			$return_obj->subject = $this->subject;
			foreach($this->attached_documents as $document)
			{
				$return_obj->attached_data[] = $document->Get_Return_Object();
			}
			$return_obj->Printed = $this->Get_Printed();
			$return_obj->Signed  = $this->Get_Signed();
			$return_obj->latest_dispatch = $this->Get_Latest_Dispatch();
			$return_obj->application_id = $this->application_id;
			$return_obj->undefined_tokens = $this->undefined_tokens;
			$return_obj->send_priority = $this->send_priority;
		}
		else 
		{
			$return_obj = FALSE;
		}
		return $return_obj;
	}
	
	/**
	 * Calls Gets the latest dispatch and it's status 
	 * and returns it as an object.
	 *
	 * @return object
	 */
	public function Get_Latest_Dispatch()
	{
		$ret = $this->Get_Dispatch_History(1);
		return $ret[0];
	}
	
	/**
	 * Returns an array of objects containing
	 * information about each dispatch record in
	 * deescending order. $limit is the 
	 * number of dispatch records to grab.
	 *
	 * @param mixed $limit
	 * @return array
	 */
	public function Get_Dispatch_History($limit=FALSE)
	{
		$doc_id = $this->Get_Archive_Id();
		$ret_val = NULL;
		if(is_numeric($doc_id))
		{
			$query = "
				SELECT
					dd.sender,
					dd.recipient,
					dd.transport,
					dd.document_dispatch_id,
					dd.date_created,
					ds.type,
					ds.name,
					dhm.message
				FROM
					document_dispatch AS dd
				LEFT JOIN dispatch_history AS dh USING (document_dispatch_id)
				LEFT JOIN dispatch_status AS ds USING (dispatch_status_id)
				LEFT JOIN dispatch_history_message AS dhm ON dhm.dispatch_history_id = dh.dispatch_history_id
				WHERE
					dd.document_id = $doc_id
				ORDER BY 
					dd.document_dispatch_id DESC, dh.dispatch_history_id ASC
			";
			if(is_numeric($limit))
			{
				$query .= "LIMIT $limit";
			}
			try
			{
				$res = $this->sql->Query($query);
				if(($res->Row_Count() > 0))
				{
					$ret_val = array();
					while($row = $res->Fetch_Object_Row())
					{
						$obj = new stdClass();
						$obj->sender = $row->sender;
						$obj->recipient = $row->recipient;
						$obj->transport = $row->transport;
						$obj->dispatch_date = $row->date_created;
						$obj->status = $row->name;
						$obj->status_type = $row->type;
						$obj->error_response = $row->message;
						$ret_val[] = $obj;
					}
				}
			}
			catch (Exception $e)
			{
				return NULL;
			}
		}
		return $ret_val;
	}
	
	/**
	 * Inserts a new document into the document table in Condor.
	 *
	 * @param string $type
	 */
	private function Insert_Document($type) {
		// Application ID is optional, if it's empty, then put in NULL
		$app_id = $this->application_id ? $this->application_id : 'NULL';
		$template_id = $this->template_id ? $this->template_id : 'NULL';

		$subject     = mysql_escape_string($this->subject);
		$track_key = mysql_escape_string($this->track_key);
		$space_key = mysql_escape_string($this->space_key);
		$unique_id = mysql_escape_string($this->unique_id);
		// Insert document into the document table
 		$insert_query = "
			INSERT INTO
				document
			SET
				date_created	= NOW(),
				template_id		= $template_id,
				type			= '$type',
				application_id	= $app_id,
				subject			= '$subject',
				user_id			= $this->user_id,
				track_key		= '$track_key',
				space_key		= '$space_key',
				unique_id		= '$unique_id'";

        //if($DEBUG_MODE) fwrite(STDOUT,$insert_query);
		try
		{
			$this->sql->Query($insert_query);
		}
		catch(Exception $e)
		{
            //if($DEBUG_MODE) fwrite(STDOUT,$e->getMessage());
			exit($e->getMessage());
		}

		$this->archive_id = $this->sql->Insert_Id();
	}
	
	/**
	 * Renders the document and all its attachments.
	 *
	 * @param array $data
	 * @param int $company_id
	 * @return bool
	 */
	public function Render($data, $company_id, $use_token_spans = FALSE)
	{

		if(!isset($this->template))
		{
			if(!$this->Get_Template($company_id))
			{
				return FALSE;
			}
		}

		// Get the initial template data
		$template_data = $this->template->Get_Template_Data();
	//	$this->subject = $this->template->Get_Subject();
		$this->template_id = $this->template->Get_Template_Id();
		$this->content_type = $this->template->Get_Content_Type();
		
		
		$provider = new Template_ArrayTokenProvider($data);
		if ($use_token_spans)
		{
			$provider = new Template_SpanDecorator($provider);
		}
		// Render the template with the given data
		$template_parser = new Template_Parser(self::TOKEN_IDENT, $provider);
		
		/*
			Render the subject stuff
		*/
		$this->subject = $template_parser->parse($this->template->Get_Subject());

		$template_parser->setTemplateData($template_data);
		
		
		/**
		 * Find any unset tokens that exist in the template, but are not
		 * defined here.
		 */
		$this->undefined_tokens = array_diff($template_parser->getTokens(), array_keys($data));
		//If we're sending alerts, AND we actually have undefined tokens,  send an alert
		if (defined('SEND_MISSING_TOKEN_ALERT') && 
			SEND_MISSING_TOKEN_ALERT === true && 
			count($this->undefined_tokens) > 0)
		{
			$this->sendMissingTokenAlert($company_id);		
		}
		
		/*
			Render and setup the root_part
		*/
		$this->root_part = new Part($this->sql, $this->user_id, $this->doc_action);
		$this->root_part->Use_Encryption($this->template->Require_Encryption());
		$this->root_part->Set_Data($template_parser->parse());
		$this->root_part->Set_Content_Type($this->content_type);

		/*
			Get the attached file data
			We don't actually do any rendering on this, but we still need to load it.
		*/
		$attached_files = $this->template->Get_Attachment_Data();

		foreach($attached_files as $file)
		{
			$new_part = new Part($this->sql, $this->user_id, $this->doc_action);
			$new_part->Set_Data($file->Get_Data());
			$new_part->Set_Content_Type($file->Get_Content_Type());
			$new_part->Set_Uri($file->Get_URI());

			$this->root_part->Add_Child($new_part);
		}

		/*
			We can't do any PDF conversion until this point as we need to have the file
			attachments. What we'll do is replace the root_part of this document with
			a new PDF part.
		*/
		if($this->content_type == CONTENT_TYPE_APPLICATION_PDF)
		{
			$this->root_part->Condor_API_Auth($this->condor_api_auth);
			$this->root_part->Render_As_PDF($this->mode);
		}

		/*
			Here's how we're going to do this. We're going to keep the Document within
			Document model, except at the end of each document creation, we'll return
			the list of Part objects it contains (sneaky ain't it) and attach them
			to our root_part. We won't have to save each document anymore. Yay!
		*/

		// Get the attached templates
		$attached_templates = $this->template->Get_Attached_Templates();

		// Render each template and add it as a document
		foreach($attached_templates as $template)
		{
			$attached_doc = new Document($this->sql, $this->user_id, $this->mode);
			$attached_doc->Set_Template($template);
			$attached_doc->Render($data);

			$this->root_part->Add_Child($attached_doc->Get_Root_Part());
		}

		return TRUE;

	}

	/**
	 * Returns the return object, but with everything converted to a PDF.
	 *
	 * @return object
	 */
	public function Get_As_PDF()
	{
		$this->root_part->Condor_API_Auth($this->condor_api_auth);
		$this->root_part->Render_As_PDF($this->mode, TRUE);

		return $this->Get_Return_Object();
	}

	/**
	 * Returns the document's return object with everything rendered as a
	 * PostScript file.
	 *
	 * @return object
	 */
	public function Get_As_PostScript()
	{
		$this->root_part->Condor_API_Auth($this->condor_api_auth);
		$this->root_part->Render_As_PostScript($this->mode, TRUE);

		return $this->Get_Return_Object();
	}

	/**
	 * Returns the root part for this document. This should only really be used
	 * by the root Document when creating a new document.
	 *
	 * @return object
	 */
	public function Get_Root_Part()
	{
		return $this->root_part;
	}

	/**
	 * Updates the document root. The root id is the base document.
	 *
	 * @param int $root_id
	 */
	private function Update_Document_Root_Id()
	{
		$query = "
			UPDATE
				document
			SET
				root_id = $this->root_id
			WHERE
				document_id = $this->archive_id";

		try
		{
			$this->sql->Query($query);
		}
		catch(Exception $e)
		{
			exit($e->getMessage());
		}
	}

	/**
	 * Sets the template.
	 *
	 * @param object $template
	 */
	public function Set_Template(&$template)
	{
		$this->template =& $template;
	}

	/**
	 * Signs the document by inserting a document action.
	 *
	 * @param int $archive_id
	 * @param string $hash
	 */
	public function Sign($archive_id, $hash, $ip_address = NULL)
	{
		if(!$this->doc_action)
		{
			$this->Document_Action();
		}

		$this->doc_action->Log_Action('SIGNED', $archive_id, $this->user_id, $hash, $ip_address);
	}

	/**
	 * Creates a new instance of the Document_Action object.
	 *
	 */
	private function Document_Action()
	{
		if(!isset($this->doc_action))
		{
			$this->doc_action = Document_Action::Singleton($this->sql);
		}
	}

	/**
	 * Returns the content type of the document.
	 *
	 * @return string
	 */
	public function Get_Content_Type()
	{
		return $this->content_type;
	}

	/**
	 * Returns the archive ID for this document.
	 *
	 * @return int
	 */
	public function Get_Archive_Id()
	{
		return $this->archive_id;
	}

	/**
	 * Returns the URI for this document.
	 *
	 * @return unknown
	 */
	public function Get_URI()
	{
		return $this->uri;
	}

	/**
	 * Returns the subject for the root document. Any attached documents will have
	 * empty subjects.
	 *
	 * @return string
	 */
	public function Get_Subject()
	{
		return $this->subject;
	}
	
	/**
	 * Returns the priority that is used when sending this document.
	 * 
	 * @return int
	 */
	public function Get_Send_Priority()
	{
		return $this->send_priority;
	}

	/**
	 * Returns an array containing any attachment data.
	 *
	 * @return array
	 */
	public function Get_Attached_Data()
	{
		$ret_val = array();

		foreach($this->attached_documents as $document)
		{
			$new_doc = new stdClass();
			$new_doc->data = $document->Get_Document_Data();
			$new_doc->attached_data = $document->Get_Attached_Data();

			$ret_val[] = $new_doc;
		}

		$ret_val = array_merge($ret_val, $this->Get_Attached_File_Data());

		return $ret_val;
	}

	/**
	 * Returns the attached file data.
	 *
	 * @return array
	 */
	private function Get_Attached_File_Data()
	{
		$ret_val = array();

		foreach($this->attached_files as $file)
		{
			$new_doc = new stdClass();
			$new_doc->data = $file->Get_Data();
			$new_doc->uri = $file->Get_URI();
			$new_doc->content_type = $file->Get_Content_Type();
			$new_doc->attached_data = $file->Get_Attached_Data();

			$ret_val[] = $new_doc;
		}

		return $ret_val;
	}

	/**
	 * Checks that the document data for this object and the data passed in
	 * are the same.
	 *
	 * @param string $document
	 * @return boolean
	 */
	public function Identical_Documents($document)
	{
		$ret_val = FALSE;

		if(md5($this->root_part->Get_Return_Object()->data) == md5($document))
			$ret_val = TRUE;

		return $ret_val;
	}


	/**
	 * Returns an array of all the part ID's for the document.
	 *
	 * @return array
	 */
	public function Get_Part_Ids()
	{
		$ret_val = array();

		$ret_val[] = $this->root_id;

		foreach($this->attached_files as $attachment)
		{
			$ret_val = array_merge($ret_val, $attachment->Get_Part_Ids());
		}

		return $ret_val;
	}

	/**
	 * Adds a Document object as an attachment to the current document.
	 *
	 * @param Document $document
	 */
	public function Add_Document_Attachment(Document $document)
	{
		$part_id_list = $document->Get_Part_Ids();

		$query = "
			INSERT INTO
				document_part
			(
				document_id,
				part_id
			)
			VALUES";

		for($i = 0; $i < count($part_id_list); $i++)
		{
			$query .= "
			(
				$this->archive_id,
				{$part_id_list[$i]}
			),";
		}

		// Remove the last comma
		$query = substr($query, 0, strlen($query) - 1);

		try
		{
			$this->sql->Query($query);
		}
		catch(Exception $e)
		{
			exit($e->getMessage());
		}
	}
	
	/**
	 * Adds a file as an attachment to the current document
	 *
	 * @param string Content-Type
	 * @param string Data
	 * @param string File Name
	 * @param string File Format
	 */
	public function Add_Email_Attachment($content_type, $data, $file_name, $file_format)
	{
		//Create the new part
		$extra_part = new Part($this->sql, $this->user_id, $this->doc_action, NULL, $this->root_id,
							   $content_type, $file_name, NULL, NULL, $data);
		//Just to make sure that anything sensitive is encrypted, we'll just 
		//assume that this is and encrypt it.
		$extra_part->Use_Encryption(true);
		$part_id = $extra_part->Save($this->archive_id, $file_format);
		$this->attached_documents[] = $extra_part;
	}

	/**
	 * Sets the track and space key for this document.
	 *
	 * @param string $track_key
	 * @param string $space_key
	 */
	public function Set_Keys($track_key, $space_key)
	{
		$this->track_key = $track_key;
		$this->space_key = $space_key;
	}

	/**
	 * Sets the subject.
	 *
	 * @param string $subject
	 */
	public function Set_Subject($subject)
	{
		$this->subject = $subject;
	}

	/**
	 * Sets the unique id for this document
	 * 
	 * The unique id is an identifier created by an outside entity
	 * @param string Unique Id
	 */
	public function Set_Unique_Id($uid)
	{
		$this->unique_id = $uid;
	}

	/**
	 * Sets the document to specific data. This is used when you don't have a template
 	* to render and you're not trying to load a document, but instead already have a
	 * document and want to add it to Condor.
	 *
	 * @param string $data
	 * @param string $content_type
	 */
	public function Set_Document_Data($data, $content_type)
	{
		$this->root_part = new Part($this->sql, $this->user_id, $this->doc_action);
		//Since we really have no idea what is in this content, we'll just assume
		//that we should go ahead and encrypt it just for the sake of being
		//totally awesome.
		$this->root_part->Use_Encryption(true);
		$this->root_part->Set_Data($data);
		$this->root_part->Set_Content_Type($content_type);
	}
	
	
	/**
	 * Sends an alert to everyone important that these tokens are not
	 * being defined. Temporary thing, but yeah.
	 *
	 */
	private function sendMissingTokenAlert($company_id)
	{
		if (EXECUTION_MODE === MODE_LIVE)
		{
			static $emails_to_alert = array(
				'mike.lively@sellingsource.com',
				//'brian.ronald@sellingsource.com',
			);
		}
		else 
		{
			static $emails_to_alert = array(
				//'brian.ronald@sellingsource.com',
			);
		}
		if (count($emails_to_alert) > 0)
		{
			$message = sprintf("A document with template '%s'".
				" was created with unpopulated tokens by user (%s) for company (%s / %d). Application Id: %s\nThe following were not defined:\n",
				$this->template_name,
				$this->getAgentLogin(),
				$this->getCompanyName($company_id),
				$company_id,
				$this->application_id
			);
			foreach ($this->undefined_tokens as $token)
			{
				$message .= "\t$token\n";
			}
			require_once('reported_exception.1.php');
			EMail_Reporter::Send($emails_to_alert, $message, "Condor: Undefined Tokens (".$this->mode.')');
		}
			
	}
	
	
	//The following 2 functions really have no business here, but for the 
	//sake of this temporary aler thing, it's here for ease
	
	/**
	 * Gets the agent login name for whatever agent created this document
	 *
	 * @return unknown
	 */
	private function getAgentLogin()
	{
		$return = "Unknown Agent";
		$query = "
			SELECT 
				login
			FROM
				condor_admin.agent
			WHERE
				agent_id = {$this->user_id}
		";
		try 
		{
			$res = $this->sql->Query($query);
			if (($row = $res->Fetch_Object_Row()))
			{
				$return = $row->login; 
			}
			else 
			{
			
			}
		}
		catch (Exception $e)
		{
			$return = $e->getMessage();
		}
		return $return;	
	}
	
	/**
	 * Gets the company name based on company_id
	 *
	 * @param unknown_type $company_id
	 * @return unknown
	 */
	private function getCompanyName($company_id)
	{
		//Returns the name_short of a company
		$return = 'Unknown Company';
		$query = "SELECT 
			name_short
		FROM
			condor_admin.company
		WHERE
			company_id= {$company_id}
		";
		try 
		{
			$res = $this->sql->Query($query);
			if (($row = $res->Fetch_Object_Row()))
			{
				$return = $row->name_short;		
			}
		}
		catch (Exception $e)
		{
			
		}
		return $return;
	}
}
?>
