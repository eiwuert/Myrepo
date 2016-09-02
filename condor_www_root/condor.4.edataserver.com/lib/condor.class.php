<?php
/**
 * Condor API class. To use this class as PRPC call, use the Prpc_Proxy class. This has been
 * implemented in condor as the condor_api.php script. To use this, you would want to make
 * a normal Prpc_Client connection with a URL similar to: prpc://condor.ds66.tss/condor_api.php.
 *
 * @author Brian Feaver
 * @copyright Copyright 2006 The Selling Source, Inc.
 */

require_once('mysqli.1.php');
require_once('prpc/client.php');
require_once('document.php');
require_once('transport_manager.php');
require_once('dispatch.php');
require_once('mysql_pool.php');
require_once('condor_exception.php');
require_once('mail_queue.php');

class Condor
{

	private $template_list;
	private $sql;
	private $db;
	private $mode;
	private $dispatch;
	private $doc_action;
	private $user_id;
	private $company_id;
	private $condor_api_auth;

	// Generally speaking, one instance of the Condor class will be associated with
	// only one document and template at a time, so saving this information in the class
	// makes sense.
	private $document;
	private $template_id;
	private $archive_id;
	private $application_id;
	private $condor_dir;
	private $log;

	const ROOT_DIR = '/data';
	//If we lose the NFS mount, use this directory
	const BACKUP_DIR = "/condor_backup";
	//The Copia Import Directory
	const COPIA_FILE = '/data/copia_import/incoming/%04u/%08u.TIF.gz';
	/*
		NOTE: Condor does not actually do gzip compression. We just use zlib compression,
		using gzcompress(), before saving the file. So it is not possible to unzip a
		Condor file using gunzip. This saves a bit of space, since we aren't writing the
		gzip headers for every file.
	*/
	const FILENAME_FORMAT = '%u_%u.gz';

	function __construct($mode, $user_id, $company_id, $username = NULL, $password = NULL)
	{
		//If we're lvie mode, tell Set_Dir that it's a mount
		if($this->Set_Dir(CONDOR_ROOT_DIR,$mode == MODE_LIVE) === false)
		{
			//The backup dir is never a mount so yeah
			if($this->Set_Dir(CONDOR_BACKUP_DIR,false) === false)
			{
				throw new Exception("Could not setup condor directory.");
			}
		}
		
		// save our mode
		$this->mode = $mode;

		// Save the user information
		$this->user_id = $user_id;
		$this->company_id = intval($company_id);
		$this->log = Condor_Applog::getInstance();

		// Setup MySQL connection
		$this->db  = MySQL_Pool::Get_Definition('condor_' . $mode);
		$this->sql = MySQL_Pool::Connect('condor_' . $mode);

		if(!is_array($this->db) && !($this->sql instanceof MySQLi_1))
		{
			throw new CondorException('Could not connect to condor database.',
				CondorException::ERROR_DATABASE);
		}
		
		//If it's being passed to us, we'll just set it
		if(!is_null($username) && !is_null($password))
		{
			$this->condor_api_auth = "$username:$password";
		}
		//Otherwise we'll grab it from the database
		else 
		{
			$query = 'SELECT 
				login,crypt_password
			FROM
				condor_admin.agent
			JOIN system USING (system_id)
			WHERE
				condor_admin.system.name_short=\'condorapi\'
			AND
				condor_admin.agent.company_id = '.$this->company_id.';
			';
			$res = $this->sql->Query($query);
			$row = $res->Fetch_Object_Row();
			$username = $row->login;
			$password = Security::Decrypt($row->crypt_password);
			$this->condor_api_auth = "$username:$password";
		}

	}
	
	/**
	 * Increments the condor_tmeplate_cache_id which will 
	 * in effect clear the cache.
	 *
	 */
	public function Clear_All_Cache()
	{
		$cache = Cache::Singleton(EXECUTION_MODE);
		$o_key = $cache->get('condor_template_cache_id');
		if($o_key === false)
		{
			$cache->Set('condor_template_cache_id',1);
			$o_key = 1;
		}
		$cache->increment('condor_template_cache_id');
		return ($cache->get('condor_template_cache_id') != $o_key);
	}
	
	/**
	 * The company id associated with this condor object
	 * @return int 
	 */
	public function Get_Company_Id()
	{
		return $this->company_id;
	}
	/**
	 * The mode condor is currently running in
	 * @return string 
	 */
	public function Get_Mode()
	{
		return $this->mode;
	}
	/**
	 * Returns the API Authorization string for this company
	 *
	 * @return string
	 */
	public function Get_API_Auth()
	{
		return $this->condor_api_auth;
	}
	
	/**
	 * Returns the agent ID that is logged in
	 */
	public function Get_Agent_Id()
	{
		return $this->user_id;
	}
	
	/**
	 * A template has been updated, clear the cache
	 *
	 * @param unknown_type $id
	 */
	public function Delete_Template_Cache($id, $template_name = NULL)
	{
		$tpl = new Template($this->sql);
		return $tpl->Delete_Cache($id, $this->company_id, $template_name, $this->mode);
	}
	
	/**
	 * Does Unique Id Exist
	 * 
	 * Does a document have a certain unique id associated with it
	 * @param string Unique ID
	 * @return int Archive ID, False if not found
	 */
	public function Does_Unique_Id_Exist($uid)
	{ 
		$query = "
			SELECT 
				document_id
			FROM 
				document
			WHERE 	
				unique_id = '" . $this->sql->Escape_String($uid) . "'";

		try
		{
			$result = $this->sql->Query($query);
		}
		catch(Exception $e)
		{
			$this->log->Write($e->getMessage());
			die($e->getMessage());
		}

		if($row = $result->Fetch_Array_Row())
		{
			return $row['document_id'];
		}
		
		return false;
	}
	
	/**
	 * Creates a NEW document with the part_id 
	 * as the root. Returns either the Archive
	 * id or false if it's dead.
	 * 
	 * @param int $archive_id
	 * @param int $part_id
	 * @return mixed
	 */
	public function Create_Document_From_Part($archive_id,$part_id)
	{
		$ret_val = FALSE;
		try 
		{
			//first lets make sure the document is owned by the user
			$this->document = new Document($this->sql,$this->user_id,$this->mode);
			$this->document->Condor_API_Auth($this->Get_API_Auth());
			if($this->document->Load($archive_id))
			{
				$doc_obj = $this->document->Get_Return_Object();
				$doc_contains_part = FALSE;
				//make sure that that part id is actually in that archive
				if($doc_obj->part_id == $part_id)
				{
					$doc_contains_part = TRUE;
				}
				else 
				{
					foreach($doc_obj->attached_data as $doc_obj)
					{
						if($doc_obj->part_id == $part_id)
						{
							$doc_contains_part = TRUE;
							break;
						}
					}
				}
				if($doc_contains_part === TRUE)
				$ret_val = $this->document->Create_From_Part($part_id);
			}
		}
		catch (Exception $e)
		{
			return FALSE;
		}
		
		return $ret_val;
	}
	
	/**
	 * Creates the document, storing it in the condor database, and returns the rendered
	 * document. If archive is true, it returns an array containing the document and the archive
	 * ID.
	 *
	 * @param string $template_name
	 * @param array $tokens
	 * @param boolean $archive
	 * @param int $application_id
	 * @param string $track_key
	 * @param string $space_key
	 * @return mixed
	 */
	public function Create(
		$template_name,
		$tokens,
		$archive = FALSE,
		$application_id = NULL,
		$track_key = '',
		$space_key = '',
		$use_token_spans = FALSE
	)
	{
		$ret_val = FALSE;

		if (is_string($template_name) && is_array($tokens))
		{

			$this->document = new Document(
				$this->sql,
				$this->user_id,
				$this->mode,
				$template_name,
				$application_id
			);

			$this->document->Set_Keys($track_key, $space_key);
			$status = $this->document->Render($tokens, $this->company_id, $use_token_spans);

			// If archive is true, we must have the application ID
			if($archive && $application_id != NULL && $status)
			{
				$dir = $this->Get_Directory();

				if($dir)
				{

					$file_format = $dir.self::FILENAME_FORMAT;

					// Insert document into the document table
					// The initial document save should be 'text/html'
					$this->archive_id = $this->document->Save(
						Document::TYPE_OUTGOING,
						CONTENT_TYPE_TEXT_HTML,
						$file_format);

					$this->Document_Action();
					$this->doc_action->Log_Action('SAVED', $this->archive_id, $this->user_id);

					$ret_val = array(
						'archive_id' => $this->archive_id,
						'document' => $this->Get_Return_Object()
					);

				}

			}
			elseif($archive)
			{
				// This is a problem. They want to archive it, but they forgot the application_id
				// Danger Will Robinson...
			}
			else
			{
				$ret_val = $this->Get_Return_Object();
			}

		}

		return $ret_val;
	}

	/**
	 * Creates a document where the attachments will be other documents transformed
	 * into PDF's. $document_id may be an int or an array and $type may be a string or an array.
	 * If $document_id is an array of document ID's and $type is a string, all the document ID's
	 * specified in $document_id will be converted to $type. If both are strings, the document ID
	 * will be converted to the $type. If both are arrays, the document ID's will be converted to
	 * the corresponding $type. Both arrays must have an equal number of elements.
	 *
	 * @param string $template_name
	 * @param mixed $document_id
	 * @param mixed $type
	 * @param array $tokens
	 * @param boolean $archive
	 * @param int $application_id
	 * @param string $track_key
	 * @param string $space_key
	 * @return mixed
	 */
	public function Create_As_Attachment(
		$template_name,
		$document_id,
		$type,
		$tokens,
		$archive = FALSE,
		$application_id = NULL,
		$track_key = '',
		$space_key = ''
	)
	{
		$ret_val = FALSE;
		$valid_params = TRUE;

		// Check if both are arrays
		if(is_array($type) && is_array($document_id) && count($type) == count($document_id))
		{
			foreach($type as $t)
			{
				if(!is_string($t))
				{
					$valid_params = FALSE;
				}
			}

			foreach($document_id as $id)
			{
				if(!($id > 0))
				{
					$valid_params = FALSE;
				}
			}
		}
		// Check if $document_id is an array and $type is a string
		elseif(is_array($document_id) && is_string($type))
		{
			foreach($document_id as $id)
			{
				if(!($id > 0))
				{
					$valid_params = FALSE;
				}
			}
		}
		else
		{
			// Check if $document_id is numeric and above 0 and $type is a string
			if(!(is_numeric($document_id) && $document_id > 0 && is_string($type)))
			{
				$valid_params = FALSE;
			}
		}

		// Do your magic!
		if(is_string($template_name) && $valid_params && is_array($tokens))
		{
			$initial_doc = $this->Create(
				$template_name,
				$tokens,
				$archive,
				$application_id,
				$track_key,
				$space_key
			);

			if($initial_doc !== FALSE)
			{
				if(is_array($document_id))
				{
					foreach($document_id as $id)
					{
						$new_doc = new Document($this->sql, $this->user_id, $this->mode);
						$new_doc->Condor_API_Auth($this->condor_api_auth);
						$new_doc->Set_Keys($track_key, $space_key);
						$new_doc->Load($id);

						$initial_doc['document']->attached_data[] = $new_doc->Get_Return_Object();
						$this->document->Add_Document_Attachment($new_doc);
					}
				}
				elseif(is_numeric($document_id) && $document_id > 0)
				{
					$attached_doc = new Document($this->sql, $this->user_id, $this->mode);
					$attached_doc->Condor_API_Auth($this->condor_api_auth);
					$attached_doc->Set_Keys($track_key, $space_key);
					$attached_doc->Load($document_id, TRUE);
					$initial_doc['document']->attached_data[] = $attached_doc->Get_Return_Object();
					$this->document->Add_Document_Attachment($attached_doc);
				}
			}

			$ret_val = $initial_doc;
		}

		return $ret_val;
	}

	/**
	 * Creates a new part to be attached to an email
	 *
	 * Adds an attachment as a new document part
	 *
	 * @param mixed $type
	 * @param mixed $document_content
	 * @return mixed
	 */
	public function Create_As_Email_Attachment($a_id, $type, $document_content, $file_name)
	{
		$this->document = new Document($this->sql, $this->user_id, $this->mode);
		$this->document->Condor_API_Auth($this->Get_API_Auth());
		if($this->document->Load($a_id))
		{
			$this->document->Add_Email_Attachment($type, $document_content, $file_name, $this->Get_Directory() . self::FILENAME_FORMAT);
			return TRUE;
		}
		else 
		{
			return FALSE;
		}
	}

	/**
	 * Returns a stdClass object of the document and all its attachments.
	 *
	 * @return object
	 */
	private function Get_Return_Object()
	{
		if($this->document instanceof Document)
		{
			return $this->document->Get_Return_Object();
		}
		return FALSE;
	}

	/**
	 * Sets the document to be signed. The date and time of the signature and a
	 * hash of the document that was displayed will be stored.
	 *
	 * @param int $archive_id
	 * @param string $document Just the data section of the displayed document
	 * @return boolean
	 */
	public function Sign($archive_id, $document, $ip_address = NULL)
	{
		$ret_val = FALSE;

		if(is_numeric($archive_id) && $archive_id > 0 && strlen($document) > 0)
		{
			// Will need to load the document first
			$this->document = new Document($this->sql, $this->user_id, $this->mode);
			$this->document->Condor_API_Auth($this->condor_api_auth);
			$this->document->Load($archive_id);

			// If the documents are identical, then sign the document and store the hash
			if($this->document->Identical_Documents($document))
			{
				$this->document->Sign($archive_id, md5($document), $ip_address);
				$ret_val = TRUE;
			}
		}

		return $ret_val;
	}

	/**
	 * Sends a document, given by the archive ID, to the recipient. It will
	 * return an array of job ID's.
	 *
	 * Note: This function has been changed to only accept a single recipient. The
	 * recipient array should have the form:
	 *
	 * Email:
	 * array(
	 * 	'email_primary' => 'email@example.com',
	 * 	'email_primary_name' => 'Email Guy'
	 * )
	 *
	 * or Fax:
	 * array(
	 * 	'fax_number' => '1234567890'
	 * )
	 *
	 * @param int $archive_id
	 * @param array $recipient
	 * @param string $method ('EMAIL'|'FAX')
	 * @param array $cover_data Data for a fax cover sheet
	 * @return bool
	 */
	public function Send($archive_id, 
		$recipient, 
		$method = 'EMAIL', 
		$cover_data = NULL, 
		$sender_from=NULL)
	{

		$ret_val = FALSE;
		$dispatch_id = FALSE;
		// Check that the recipients array is valid numbers/email addresses
		// We can do this by just checking that they're valid 10 digit numbers and emails

		if($archive_id > 0 && is_array($recipient))
		{
			$this->archive_id = $archive_id;
			$this->document = new Document($this->sql, $this->user_id, $this->mode);
			$this->document->Condor_API_Auth($this->condor_api_auth);

			/**
			 * Sending PDFs by fax breaks the formatting.
			 * Send attachments as postscript 
			 */
			if(strcasecmp($method,'fax') === 0)
			{
				$render_pdf = FALSE;
				$render_parts_as_pdf = FALSE;
				$number = $recipient['fax_number'];
				$recipient = array('email_primary' => $number .'@myfax.com',
               'email_primary_name' => $number .'@efax.com'
);
			}
			else//Default
			{
				$render_pdf = FALSE;
				$render_parts_as_pdf = TRUE;
			}
			
			if($this->document->Load($this->archive_id,$render_pdf,$render_parts_as_pdf))
			{
				//WE successfully loaded. Now we'll 
				//make sure that the document data is ACTUALLY
				//there. If its not, we'll assume something broke
				//and return false and send an email alert out
				//just so someones aware. Since it's probably
				//a mount issue, we'll include the server IP
				//for easier tracking
				$dobj = $this->document->Get_Return_Object();
				if(!is_object($dobj) || empty($dobj->data))
				{
					$msg = "Document loaded with NULL data while sending.
						Archive Id: $archive_id
						Server IP: {$_SERVER['SERVER_ADDR']}";
					//make a new condorException (we don't throw it because we simply want
					//to report the error and then return), and then return false to say we
					//failed to queue the document.
					$reporter = new CondorException($msg,CondorException::ERROR_MOUNT);
					return FALSE;
				}
				// Beam me up Scotty!
				$transporter = Transport_Manager::Get_Transport(
					$method,
					$this->mode,
					$this->condor_api_auth,
					$this,
					$this->sql
				);
				//default the cover_page to NULL
				$cover_page = NULL;
				if($transporter !== FALSE)
				{
					$this->dispatch = Dispatch::Singleton($this->sql, $this->db['database'], $this->mode);
					if(strcasecmp($method,'FAX') == 0)
					{
						$from = $this->Get_From($method);
						$send_from = $this->Get_Email_From($from);
						//$sender = array('fax_number' => $from);
$sender = array('email_primary' => $from);
						//load up the cover page if we have cover data
						if(is_array($cover_data))
						{
							$cover_page = $this->Render_Cover_Page($cover_data);
						}
					}
					elseif (strcasecmp($method,'EMAIL') == 0) 
					{
						if($sender_from === NULL)
						{
							$sender_from = $this->Get_From('EMAIL');
						}
						$send_from = $this->Get_Email_From($sender_from);
						$from = $sender_from;
						$sender = array('email_primary' => $from);
					}
	
					try
					{
						if(isset($from) && isset($sender))
						{
							$dispatch_id = $this->dispatch->Add_Dispatch(
								$this->archive_id,
								$method,
								$recipient, // Receiving email or fax number
								$sender, // Sending email or fax number
								$this->user_id
							);
							if($dispatch_id !== FALSE)
							{
								if(!$this->Is_Blacklisted($recipient))
								{
									//IF we don't know who to send from, 
									//just fail the dispatch
									if($send_from !== FALSE)
									{
										$ret_val = $transporter->Send(
											$recipient, 
											$this->document, 
											$dispatch_id, 
											$send_from,
											$cover_page);
 //$this->log->Write('attempted to send' . print_r(array($recipient,  $dispatch_id, $send_from), true) . print_r($ret_val, true));

									}
									if(!$ret_val)
									{
										$this->Update_Status($dispatch_id, 'failed', 'FAIL');
									}
									else
									{
										$this->Update_Status($dispatch_id, 'queued');
									}
								}
								else 
								{
									$this->Update_Status($dispatch_id, 'blacklisted', 'FAIL');
									$ret_val = 0;
								}
							}
						}
					}
					catch (Exception $e)
					{
						$this->log->Write("An Exception was encountered: " . $e->getMessage());
						$this->log->Write($e->getTraceAsString());

						//if we got as far as having a dispatch
						//update it to failed.
						if($dispatch_id !== FALSE)
						{
							$this->Update_Status($dispatch_id, 'failed', 'FAIL');
						}
						$ret_val = FALSE;
					}

					$this->Document_Action();

					switch($method)
					{
						case Transport_Manager::METHOD_EMAIL:
							$this->doc_action->Log_Action('SEND_EMAIL', $this->archive_id, $this->user_id);
							break;

						case Transport_Manager::METHOD_FAX:
							$this->doc_action->Log_Action('SEND_FAX', $this->archive_id, $this->user_id);
							break;

					}
				}
			}
		}

		return $ret_val;

	}
	
	/**
	 * Query and find out if an email/fax is on the blacklist
	 *
	 * @param string $recipient
	 * @return boolean
	 */
	public function Is_Blacklisted($recipient)
	{
		
		$ret_val = false;
		$r = false;
		if(isset($recipient['email_primary']))
		{
			$r = $this->sql->Escape_String(strtolower($recipient['email_primary']));
		}
		elseif(isset($recipient['fax_number']))
		{
			$r = $this->sql->Escape_String(preg_replace('/[^\d]/','',$recipient['fax_number']));
		}
		if($r !== false)
		{
			$query = "SELECT
				count(*) as cnt
			FROM
				blacklist
			WHERE
				recipient = '$r'
			";
			try
			{
				$res = $this->sql->Query($query);
				$row = $res->Fetch_Object_Row();
				$ret_val = ($row->cnt > 0);
			}
			catch (Exception $e)
			{
				
			}
		}
		return $ret_val;
	}
	
	/**
	 * Method to add an email address to the Blacklist
	 * 
	 * Warning!  There is no ACL, so ANYONE with access to the Condor API
	 * can add to the global blacklist!
	 *
	 * @param string $recpient
	 * @return bool
	 */
	public function Add_Blacklist_Recipient($recipient)
	{
		$ret_value = FALSE;
		if(! empty($recipient))
		{
			$r = $this->sql->Escape_String(strtolower($recipient));
			$query = "INSERT IGNORE INTO blacklist (recipient) VALUES ('$recipient')";
			try
			{
				if($result = $this->sql->Query($query))
					$ret_value = TRUE;
			}
			catch (Exception $e) 
			{
				$this->log->Write("EXCEPTION: " . $e->getMessage());
				$this->log->Write("EXCEPTION TRACE: " . $e->getTraceAsString());
			}
		}

		return $ret_value;
	}

	/**
	 * Finds the latest dispatch_id for a given archive_id
	 *
	 * @param int $archive_id
	 * @return int on success, NULL on failure
	 */
	private function Get_Dispatch_Id_By_Archive_Id($archive_id)
	{
		$dispatch_id = NULL;

		if(is_numeric($archive_id) && $archive_id > 0)
		{
			/**
			 * In case the document has been re-sent, we're just going to get
			 * the most recent dispatch_id for it.
			 */
			$query = "
				SELECT document_dispatch_id 
				FROM document_dispatch 
				WHERE document_id = {$archive_id} 
				ORDER BY document_dispatch_id DESC 
				LIMIT 1";
			try
			{
				$result = $this->sql->Query($query);
				$row = $result->Fetch_Object_Row();
				$dispatch_id = $row->document_dispatch_id;
			}
			catch (Exception $e) 
			{
				$this->log->Write("EXCEPTION: " . $e->getMessage());
				$this->log->Write("EXCEPTION TRACE: " . $e->getTraceAsString());
			}
		}

		return $dispatch_id;
		
	}
	
	/**
	 * Handles a bounced email by updating the message status and adding 
	 * the address to the blacklist
	 *
	 * @param unknown_type $archive_id
	 * @param unknown_type $recipient_address
	 * @param unknown_type $bounce_message
	 * @return unknown
	 */
	public function Add_Bounce($archive_id = NULL, $recipient_address = '', $bounce_message = '')
	{
		if(! is_numeric($archive_id) || $archive_id <= 0)
		{
			return FALSE;
		}
		
		if($dispatch_id = $this->Get_Dispatch_Id_By_Archive_Id($archive_id))
		{
			$retval_one = $this->Update_Status($dispatch_id, 'bounced', 'FAIL', $bounce_message);
			$retval_two = $this->Add_Blacklist_Recipient($recipient_address);
			
			if($retval_one === TRUE && $retval_two === TRUE)
				return TRUE;

			if($retval_one != TRUE) $this->log->Write("Update status failed for archive_id $archive_id, dispatch_id of $dispatch_id");
			if($retval_two != TRUE) $this->log->Write("Failed adding blacklist for '$recipient_address'");

		}
		else
		{
			$this->log->Write("Unable to locate document_dispatch_id from archive_id '{$archive_id}'");
		}
		
		return FALSE;
	}

	/**
	 * Returns the account id related to the appropriate mail_from
	 *
	 * @param ustring $mail_from
	 */
	private function Get_Email_From($mail_from)
	{
		if(is_null($mail_from) || strpos($mail_from,'@') === FALSE)
		{
			$mail_from = $this->Get_From('EMAIL');
		}
		$s_mail_from = $this->sql->Escape_String($mail_from);
		$query= "
			SELECT
				account_id
			FROM
				condor_admin.pop_accounts
			WHERE
				company_id = {$this->company_id}
			AND
				mail_from = '$s_mail_from'
				
			AND
				direction IN ('OUTGOING','BOTH')
			LIMIT 1
		";
		try 
		{
			$ret_val = FALSE;
			$res = $this->sql->Query($query);	
			if(($row = $res->Fetch_Object_Row()))
			{
				$ret_val = $row->account_id;
			}
			else 
			{
				$mail_from = $this->Get_From('EMAIL');
				$ret_val = $this->Get_Email_From($mail_from);
			}
		}
		catch (Exception $e)
		{
			return FALSE;
		}
		
		return $ret_val;
	}
	
	/**
	 * Returns the from field for the transport method and company_id.
	 *
	 * @return string
	 */
	private function Get_From($transport)
	{
		$from = '';

		$query = "
			SELECT
				company_from
			FROM
				company_from
			WHERE
				company_id = $this->company_id
			AND 
				transport = '$transport'";

		try
		{
			$result = $this->sql->Query($query);
		}
		catch(Exception $e)
		{
			die($e->getMessage());
		}

		if(($row = $result->Fetch_Object_Row()))
		{
			$from = $row->company_from;
		}

		return $from;
	}

	/**
	 * Returns the document associated with the given ID. If the optional
	 * second argument is set to true and the document is stored as a tiff
	 *
	 * @param int $id
	 * @param bool $convert_to_tiff
	 * @return object
	 */
	public function Find_By_Archive_Id($id, $convert_to_tiff=FALSE)
	{
		/*
			I removed the object parameter as we really actually don't want anyone
			using this interface to have direct access to the document object. [BF]
		*/

		$ret_val = FALSE;

		if(is_numeric($id) && $id > 0)
		{
			$this->document = new Document($this->sql, $this->user_id, $this->mode);
			 $this->document->Condor_API_Auth($this->condor_api_auth);

			// Load the document
			if($this->document->Load($id))
			{
				$this->Document_Action();
				$this->doc_action->Log_Action('LOADED', $id, $this->user_id);
				$ret_val = $this->Get_Return_Object();
				if($convert_to_tiff === FALSE && 
					$ret_val->content_type==CONTENT_TYPE_IMAGE_TIFF)
				{
					$ret_val->data = $this->Tiff_To_PDF($ret_val->data);
					$ret_val->content_type = CONTENT_TYPE_APPLICATION_PDF;
				}
			}
		}

		return $ret_val;
	}
	
	/**
	 * Returns the document associated with the given ID. If the optional
	 * second argument is set to true then convert the data to plain text
	 *
	 * @param int $id
	 * @param bool $convert_to_plain_text
	 * @return object
	 */
	public function Find_Email_By_Archive_Id($id, $convert_to_plain_text=FALSE)
	{
		$ret_val = FALSE;
		if(is_numeric($id) && $id > 0)
		{
			$this->document = new Document($this->sql, $this->user_id, $this->mode);
			$this->document->Condor_API_Auth($this->condor_api_auth);

			// Load the document
			if($this->document->Load($id, FALSE, FALSE))
			{
				$this->Document_Action();
				$this->doc_action->Log_Action('LOADED', $id, $this->user_id);
				$ret_val = $this->Get_Return_Object();
				if($convert_to_plain_text)
				{
					//Convert to plain text	
					//Doc
					$ret_val->data = trim(html_entity_decode(strip_tags($ret_val->data)));
				}
			}
			else 
			{
				$ret_val = FALSE;
			}
		}

		return $ret_val;
	}

	/**
	 *
	 * @param int $id
	 * @param bool $convert_to_plain_text
	 * @return object
	 */
	public function Get_History_By_Archive_Id($id)
	{
		$ret_val = FALSE;
		if(is_numeric($id) && $id > 0)
		{
			$this->document = new Document($this->sql, $this->user_id, $this->mode);
			$this->document->Condor_API_Auth($this->condor_api_auth);

			// Load the document
			if($this->document->Load($id, FALSE, FALSE))
			{
				$ret_val = $this->document->Get_Dispatch_History();
				
			}
			else 
			{
				$ret_val = FALSE;
			}
		}

		return $ret_val;
	}

	/**
	 * Retrieves and returns document_id's associated with the application ID provided.
	 * Also returns the template name for the document, the date the document was
	 * created, and the type of document (incoming or outgoing). Returns boolean FALSE
	 * if application ID is invalid.
	 *
	 * @param int $application_id
	 * @return array
	 */
	public function Find_By_Application_Id($application_id)
	{
		$document_id_list = array();

		if($application_id > 0)
		{
			// Grab all the document_id's for the application_id
			$query = "
				SELECT
					d.document_id,
					d.date_created,
					d.type,
					t.name AS template_name
				FROM
					document d
					JOIN template t ON d.template_id = t.template_id
				WHERE
					d.application_id = $application_id";

			try
			{
				$result = $this->sql->Query($query);

				while(($row = $result->Fetch_Array_Row()))
				{
					$document_id_list[] = array(
						'document_id' => $row['document_id'],
						'date_created' => $row['date_created'],
						'type' => $row['type'],
						'template_name' => $row['template_name']
					);
				}
			}
			catch(Exception $e)
			{
				$document_id_list = NULL;
			}
		}
		else
		{
			$document_id_list = FALSE;
		}

		return $document_id_list;
	}

	/**
	 * Returns a list of templates.
	 *
	 * @return array
	 */
	public function Get_Template_Names()
	{
		// Get a list of all template names and store them in an array. Return the array.
		$templates = array();

		try
		{
			$query = "SELECT 
				t.name, 
				t.template_id
				FROM 
					template t
				LEFT JOIN 
					shared_template st 
				ON (
					t.template_id = st.template_id AND 
					st.company_id = $this->company_id
				)
				WHERE
					t.status = 'ACTIVE'
				AND 
					(t.company_id = $this->company_id OR (st.company_id = $this->company_id AND t.company_id != $this->company_id))";
			$result = $this->sql->Query($query);
			while(($row = $result->Fetch_Array_Row()))
			{
				$templates[$row['template_id']] = $row['name'];
			}
		}
		catch(Exception $e)
		{
			$templates = NULL;
		}

		return $templates;
	}

	/**
	 * Returns the directory to use for the current file.
	 * It will create the directory if it doesn't already exist.
	 *
	 * @return string The directory to write this file into.
	 */
	public function Get_Directory()
	{
		$ret_val = FALSE;

		$dir = CONDOR_ROOT_DIR.'/'.date('Ymd').'/';

		//Check to see if we called statically or from an actual instance
		if(isset($this) && get_class($this) == __CLASS__)
		{
			$condor_dir = $this->condor_dir;
		}
		else 
		{
			$condor_dir = self::ROOT_DIR;
		}
		$dir = $condor_dir.'/'.date('Ymd').'/';

		// Check that the directory exists. If it doesn't, then create it
		if(!is_dir($dir))
		{
			//Recursively create the path
			if(mkdir($dir,0755,TRUE))
			{
				$ret_val = $dir;
			}
			else
			{
				throw new CondorException("Could not create directory {$dir}",CondorException::ERROR_MOUNT);
			}
		}
		else
		{
			$ret_val = $dir;
		}
		
		// if we are using a remote server, do the same there
		if(CONDOR_REMOTE_SERVER)
		{
			$connection = ssh2_connect(CONDOR_REMOTE_SERVER, CONDOR_REMOTE_PORT);
			ssh2_auth_password($connection,CONDOR_REMOTE_USER,CONDOR_REMOTE_CRED);
			$sftp = ssh2_sftp($connection);

			if (!(opendir("ssh2.sftp://".$sftp."/condor".$dir))) {
				//create the path
				if(ssh2_sftp_mkdir($sftp,"/condor".$dir,0755,TRUE))
				{
					$ret_val = $dir;
				}
				else
				{
					throw new CondorException("Could not create remote file server directory {$dir} on {CONDOR_REMOTE_SERVER}",CondorException::ERROR_MOUNT);
				}
				
			}
		}
		return $ret_val;
	}

	/**
	 * Flags a document as being printed. It can take either an array
	 * of document ids or just one. Returns an array of document ids
	 * with true/false based on whether it flagged the document or not.
	 *
	 * @param mixed $doc_ids
	 * @return mixed
	 */
	public function Flag_As_Printed($doc_ids)
	{
		$this->Document_Action();
		if(!is_array($doc_ids)) $doc_ids = Array($doc_ids);
		$return = Array();
		//loop through the document ids, load them, Flag them as Printed
		//and return an array containing the doc_id => response
		foreach($doc_ids as $id)
		{
			$doc = new Document($this->sql,$this->user_id,$this->mode);
			$doc->Condor_API_Auth($this->Get_API_Auth());
			if($doc->Load($id))
			{
				$return[$id] = $doc->Printed();
			}
			else 
			{
				$return[$id] = FALSE;
			}
			unset($doc);
		}
		//If we don't have anything in the return Array, return FALSE
		return (count($return) > 0) ? $return : FALSE;
	}

	/**
	 * Returns an array of stdClass objects containing data
	 * about each time the document was flagged as printed. Or false
	 * if it can't find the document.
	 *
	 * @param int $doc_id
	 * @return mixed
	 */
	public function Get_Printed_Dates($doc_id)
	{
		$return = FALSE;
		$doc = new Document($this->sql,$this->user_id,$this->mode);
		$doc->Condor_API_Auth($this->Get_API_Auth());
		if($doc->Load($doc_id))
		{
			$return = $doc->Get_Printed();
		}
		return $return;
	}

	/**
	 * Creates a new instance of the Document_Action object.
	 *
	 */
	private function Document_Action()
	{
		if(!isset($this->doc_action))
		{
			$this->doc_action = Document_Action::Singleton($this->sql, $this->db['database']);
		}
	}

	/**
	 * Updates the dispatch history for a sent document.
	 *
	 * @param int $dispatch_id
	 * @param string $status
	 * @param string Type
	 * @param string Status message
	 */
	public function Update_Status($dispatch_id, $status, $type = 'INFO', $message = '')
	{
		$ret_val = FALSE;

		if($dispatch_id > 0 && is_string($status) && is_string($type))
		{
			$this->dispatch = Dispatch::Singleton($this->sql, $this->db['database'], $this->mode);
			$this->dispatch->Log_Dispatch_Status($dispatch_id, $status, $type, $message);

			$ret_val = TRUE;
		}

		return $ret_val;
	}
	
	/**
  	* Returns an array of incoming identifiers for this company
  	*/
  	public function Get_Incoming_Id_Names()
  	{
  		$return = false;
  		$query = 'SELECT 
  					identifier
  				FROM
  					incoming_identifier
  				WHERE
  					company_id='.$this->company_id;
  		try 
  		{
  			$res = $this->sql->Query($query);
			$return = array();
			while(($row = $res->Fetch_Object_Row()))
			{
				$return[] = $row->identifier;
			}
  		}
  		catch (Exception $e)
  		{
  			
  		}
  		return $return;
  	}
  	
  	/**
  	 * Returns the Incoming Id Name for Received on. Or 
  	 * false if it can't find one.
  	 *
  	 * @param string $received_on
  	 * @return string
  	 */
  	public function Get_Incoming_Id_By_Received($received_on)
  	{
  		$return = false;
  		$s_rec_on = $this->sql->Escape_String($received_on);
  		$query = "SELECT
  				identifier
  			FROM
  				incoming_identifier
  			WHERE
  				company_id = {$this->company_id}
  			AND
  				recipient = '{$s_rec_on}'
  			LIMIT 1";
  		try 
  		{
  			$res = $this->sql->Query($query);
  			if(($row = $res->Fetch_Object_Row()))
  			{
  				$return = $row->identifier;
  			}
  		}
  		catch (Exception $e)
  		{
  		}
  		return $return;
  	}
  	
  	/**
  	 * Return all the 'received_on' things that relate to 
  	 * an incoming id.
  	 *
  	 * @param string $incoming_id
  	 * @return array
  	 */
  	public function Get_Received_For_Incoming_Id($incoming_id)
  	{
  		$return = false;
  		$s_inc_id = $this->sql->Escape_String($incoming_id);
  		$query = "SELECT
  				recipient
  			FROM
  				incoming_identifier
  			WHERE
  				company_id = {$this->company_id}
  			AND
  				identifier = '{$s_inc_id}'";
  		try 
  		{
  			$res = $this->sql->Query($query);
  			$return = array();
  			while(($row = $res->Fetch_Object_Row()))
  			{
  				$return[] = $row->recipient;
  			}
  		}
  		catch (Exception $e)
  		{
  			
  		}
  		return $return;
  	}
  	
  	/**
	 * Returns an array of queue names for this company
  	 *
  	 * @return array
  	 */
  	public function Get_Queue_Names()
  	{
		return $this->Get_Incoming_Id_Names();	
  	}
  	
	/**
	 * Takes an phone number and returns the queue(if there is one)
	 * that that number belongs in
	 *
	 * @param int $incoming_number
	 * @return mixed
	 */
	public function Get_Queue_By_Number($incoming_number)
	{
		return $this->Get_Incoming_Id_By_Received($incoming_number);
	}
	
	/**
	 * Returns an array containing all numbers belonging to a queue
	 * or false 
	 * @return mixed
	 */
	public function Get_Numbers_In_Queue($queue)
	{
		return $this->Get_Received_For_Incoming_Id($queue);
	}
	
	/**
	 * Inserts incoming documents into Condor.
	 *
	 * @param string $type
	 * @param string $sender
	 * @param int $modem_number
	 * @param string $content_type
	 * @param string $data
	 * @param string $pages
	 */
	public function Incoming_Document($type = 'FAX', 
		$sender, 
		$recipient, 
		$content_type, 
		$data, 
		$pages, 
		$subject = '', 
		$unique_id = '')
	{
		$ret_val = FALSE;

		if(!empty($data))
		{
			$this->document = new Document($this->sql, $this->user_id, $this->mode);
			$this->document->Condor_API_Auth($this->condor_api_auth);
			$this->document->Set_Document_Data($data, $content_type);
			$this->document->Set_Subject($subject);
			$this->document->Set_Unique_Id($unique_id);
			$dir = $this->Get_Directory();
		
			if($dir) {
				$file_format = $dir.self::FILENAME_FORMAT;
				$this->archive_id = $this->document->Save(
					Document::TYPE_INCOMING,
					$content_type,
					$file_format,
                    NULL
				);
				$this->document->Set_Page_Count($pages);
 				if($this->archive_id) {
                    //if($DEBUG_MODE) fwrite(STDOUT,"   Saved ". $pages ." pages \n");
					$ret_val = $this->archive_id;
				} else {
                    //if($DEBUG_MODE) fwrite(STDOUT,"   Failed, sql save error \n");
                }
			} else {
                //if($DEBUG_MODE) fwrite(STDOUT,"   Failed, no directory \n");
            }
			
			//Sender
			$s = array();
			if(is_numeric($sender))
			{
				$s['fax_number'] = $sender;
			}
			else
			{
				$s['email_primary'] = $sender;
			}
			
			//Recipient
			$r = array();
			if(is_numeric($recipient))
			{
				$r['fax_number'] = $recipient; 
			}
			else
			{
				$r['email_primary'] = $recipient;
			}
	
			$this->dispatch = Dispatch::Singleton($this->sql, $this->db['database'], $this->mode);
			$this->dispatch->Add_Dispatch(
				$this->archive_id,
				$type,
				$r,
				$s,
				$this->user_id
			);
		} else {
            //if($DEBUG_MODE) fwrite(STDOUT,"   Failed, no data \n");
        }
		return $ret_val;
	}

	/**
	 * Pulls all of the incoming documents from the document table and returns
	 * a list of archive/document ID's and the date they were received. All 
	 * paramaters are optional.
	 * 
	 * @param date start_date The date to grab documents after
	 * @param date end_date The date to grab documents before
	 * @param int modem_number The number the document was received on
	 * @param bool unprinted_only Only grab documents that were unprinted
	 *
	 * Each item in the list is a stdClass object with the following variables:
	 *
	 * <ul>
	 * <li>archive_id - the archive/document_id</li>
	 * <li>date_received - the date/time the document was received/created</li>
	 * <li>sender</li>
	 * <li>recipient</li>
	 * </ul>
	 *
	 * @return array
	 */
	public function Get_Incoming_Documents($start_date=NULL,
										   $end_date=NULL,
										   $recipient=NULL,
										   $unprocessed_only=FALSE,
										   $transport='FAX')
	{
		$received_docs = array();

		$query = "
			SELECT
				document.document_id,
				document.date_created,
				document.page_count,
				part.content_type,
				document_dispatch.sender,
				document_dispatch.recipient
			FROM
				document
				JOIN document_dispatch USING (document_id)
				JOIN condor_admin.agent ON document.user_id = agent.agent_id
				JOIN part ON (document.root_id = part.part_id)
			WHERE
				document.type = 'INCOMING'
				AND document.application_id <=> NULL
				AND document_dispatch.transport = '" . $transport . "'
				AND agent.company_id = $this->company_id";
				
		if($start_date != NULL)
		{
			if($end_date == NULL)
			{
				$end_date = date('YmdHis');
			}
			$query .= " AND document.date_created BETWEEN '$start_date' AND '$end_date'";
		}
		elseif($start_date == NULL && $end_date != NULL)
		{
			$query .= " AND document.date_created <= '$end_date'";
		}
		if($recipient != NULL)
		{
			$query .= " AND LOWER(document_dispatch.recipient)=LOWER('" . mysql_escape_string($recipient) . "')";
		}
		if($unprocessed_only === TRUE)
		{
			$this->Document_Action();
			if($transport == 'FAX')
			{
				$processed_act_id = $this->doc_action->Get_Action_Id('PRINTED');
			}
			elseif($transport == 'EMAIL')
			{
				$processed_act_id = $this->doc_action->Get_Action_Id('RECEIVED');
			}
			
			$query .= " AND 0 = (
				SELECT 
					count(*) 
				FROM 
					action_history 
				WHERE
					document_action_id='$processed_act_id'
				AND
					action_history.document_id = document.document_id
				)";
		}
		try
		{
			//return $query;
			$result = $this->sql->Query($query);
		}
		catch(Exception $e)
		{
			die($e->getMessage());
		}

		while(($row = $result->Fetch_Object_Row()))
		{
			$doc = new stdClass();
			$doc->archive_id = $row->document_id;
			$doc->date_received = $row->date_created;
			$doc->sender = $row->sender;
			$doc->recipient = $row->recipient;
			if($transport == 'FAX') $doc->queue = $this->Get_Queue_By_Number($doc->recipient);
			$doc->content_type = $row->content_type;
			$doc->page_count = $row->page_count;
			$received_docs[] = $doc;
		}
		
		//Mark documents recieved if transport is email
		if($transport == 'EMAIL' && $unprocessed_only == 'TRUE')
		{
			foreach($received_docs as $doc)
			{
				$this->doc_action->Log_Action('RECEIVED', $doc->archive_id, $this->user_id);
			}
		}
		
		return $received_docs;
	}
	
	/**
	 * Returns an array of document ids that failed to be sent. Optionally
	 * you can provide a transport (defaults to grab both). The transport
	 * can be EMAIL or FAX. Anything else and it'll grab both. Start/End
	 * dates can also be provided to grab failed sent documents between those 
	 * dates
	 *
	 * @param string $transport
	 * @param date $start_date
	 * @param date $end_date
	 * 
	 * @return array
	 */
	public function Get_Failed($transport=NULL, $start_date=NULL, $end_date=NULL)
	{
		$this->dispatch = Dispatch::Singleton($this->sql, $this->db['database'], $this->mode);
		$status_id =$this->dispatch->Get_Dispatch_Status_Id('failed',Dispatch::TYPE_FAIL);
		
		//build the where constraints for our query.
		$wheres = array();
		$wheres[] = 'agent.company_id = '.$this->company_id;

		//setup the transport constraint
		if($transport != NULL)
		{
			//Lets see how they want to limit by  transport
			if(!is_array($transport))
			{
				$wheres[] = 'dd.transport = \''.$this->sql->Escape_String($transport).'\'';
			}
			else 
			{
				foreach($transport as $k=>$t)
				{
					$transport[$k] = "'".$this->sql->Escape_String($t)."'";
				}
				$wheres[] = 'dd.transport IN ('.join(',',$transport).')';
			}
		}

		//setup date constraint
		if(!is_null($start_date))
		{
			//validate the date formatting
			list($year,$month,$day,$hour,$minute,$second) = sscanf($start_date,"%4d%2d%2d%2d%2d%2d");
			if(mktime($hour,$minute,$second,$month,$day,$year) < 1)
			{
				return FALSE;;
			}
			//if there's no end date, the end date is now!
			if(is_null($end_date))
			{
				$end_date = date('YmdHis');
			}
			//validate the end date
			list($year,$month,$day,$hour,$minute,$second) = sscanf($end_date,"%4d%2d%2d%2d%2d%2d");
			if(mktime($hour,$minute,$second,$month,$day,$year) < 1)
			{
				return FALSE;
			}
			$wheres[] = "dd.date_created BETWEEN '$start_date' AND '$end_date'";
		}
		$wheres[] = 'dh.dispatch_status_id = '.$status_id;
		$wheres[] = 'dd.document_dispatch_id = ( 
				SELECT
					max(document_dispatch_id)
				FROM
					document_dispatch ddb
				WHERE
					ddb.document_id = dd.document_id
				AND
					ddb.sender = dd.sender
				AND
					ddb.recipient = dd.recipient
			)
		';	
		if(count($wheres) > 0)
		{
			$where_string = "WHERE ".join(' AND ',$wheres);	
		}
		else
		{
			$where_string = '';
		}

		/**
		 * This monster of a query  will basically grab the information relating
		 * to the latest dispatch attempt for each document. We'll then loop through
		 * them and do the status comparison. We have to do it this way otherwise we're
		 * stuck using a subselect to make sure that this is the latest dispatch attempt for
		 * this document_id, and this seemed to perform better. I guess we'll find out.
		 */
		$query = "
			SELECT
				dd.transport,
				dd.recipient,
				dd.sender,
				dd.date_created as date_attempted,
				dd.document_id as archive_id
			FROM
				document_dispatch dd
			JOIN dispatch_history dh USING	(dispatch_history_id)
			JOIN document USING (document_id)
			JOIN condor_admin.agent ON (document.user_id = agent.agent_id) 
			$where_string";

		try 
		{
			$return = array();
			$res = $this->sql->Query($query);
			while(($row = $res->Fetch_Object_Row()))
			{
				$return[] = $row;
			}
			return $return;
		}
		catch (Exception $e)
		{
			return FALSE;
		}
		return FALSE;
	}

	/**
	 * Sets the application ID for a document.
	 *
	 * It will only update the application ID if the type is INCOMING.
	 *
	 * Will return boolean TRUE if successful, FALSE if there was an error or
	 * if it couldn't update the document.
	 *
	 * @param int $document_id
	 * @param int $application_id
	 * @return bool
	 */
	public function Set_Application_Id($document_id, $application_id)
	{
		$ret_val = false;

		if(is_numeric($document_id) && $document_id > 0 &&
			is_numeric($application_id) && $application_id > 0)
		{
			try
			{
				$query = "
					UPDATE document
				SET
					application_id = $application_id
				WHERE
					document_id = $document_id
				AND
					type = 'INCOMING'
				AND
					(
						SELECT
							company_id
						FROM
							condor_admin.agent
						WHERE
							agent_id='{$this->user_id}'
						LIMIT 1
					)='{$this->company_id}'
				";
				$this->sql->Query($query);
				$ret_val = TRUE;
			}
			catch(Exception $e)
			{
				// Do nothing
			}
		}
		return $ret_val;
	}

	public function Get_All_Pop_Accounts($direction=NULL)
	{
		$dir_where = '';
		if(!is_null($direction) && is_string($direction))
		{
			$s_dir = $this->sql->Escape_String($direction);
			$dir_where = " AND direction='$s_dir'";
		}
		
		$query = "
			SELECT
				account_id,
				reply_to,
				from_domain,
				mail_server,
				mail_port,
				mail_box,
				mail_user,
				mail_pass,
				account_name,
				direction
			FROM
				condor_admin.pop_accounts
			WHERE
				company_id={$this->company_id}
			$dir_where";
		try 
		{
			$ret_val = FALSE;
			$res = $this->sql->Query($query);
			if($res->Row_Count() > 0)
			{
				$ret_val = array();
				while($row = $res->Fetch_Object_Row())
				{
					$row->mail_pass = Security::Decrypt($row->mail_pass);
					$ret_val[$row->account_name] = $row;
					
				}
			}
			
			return $ret_val;
		}
		catch (Exception $e)
		{
			return FALSE;
		}
		return FALSE;
		
	}

	/**
	 * Returns pop account info based on the name and optionally
	 * the direction 
	 *
	 * @param string $name
	 * @param object $direction
	 * @return unknown
	 */
	public function Get_Pop_Account_By_Name($name,$direction=NULL)
	{
		$s_name = $this->sql->Escape_String($name);
		
		$dir_where = '';
		if(!is_null($direction) && is_string($direction))
		{
			$s_dir = $this->sql->Escape_String($direction);
			$dir_where = " AND direction='$s_dir'";
		}
		
		$query = "
			SELECT
				account_id,
				reply_to,
				from_domain,
				mail_server,
				mail_port,
				mail_box,
				mail_user,
				mail_pass,
				direction
			FROM
				condor_admin.pop_accounts
			WHERE
				company_id={$this->company_id}
			AND
				account_name='{$s_name}'
			$dir_where
			LIMIT 1
		";
		try 
		{
			$ret_val = FALSE;
			$res = $this->sql->Query($query);
			if($row = $res->Fetch_Object_Row())
			{
				$ret_val = $row;
				$ret_val->mail_pass = Security::Decrypt($row->mail_pass);
			}
			return $ret_val;
		}
		catch (Exception $e)
		{
			return FALSE;
		}
		return FALSE;
	}

	/**
	 * Returns an array of tokens for a given template.
	 * @param string $template_name
	 * @return array
	 */
	public function Get_Template_Tokens($template_name)
	{
		// load the template
		$template = new Template($this->sql);

		if ($template->Load_Template_By_Name($template_name, $this->company_id))
		{
			return $template->Get_Tokens(FALSE);
		}
		else
		{
			throw new Exception('Invalid template.');
		}

	}
	
	/**
	 * Returns the Raw Template data based on name/id
	 * @param string $name
	 * @return mixedd
	 */
	public function Get_Raw_Template_Data($name)
	{
		$template = new Template($this->sql);
		if($template->Load_template_By_Name($name,$this->company_id))
		{
			return $template->Get_Template_Data();
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Returns true if the token exists in condor_admin,
	 * false if it does not.
	 *
	 * @param string $token
	 * @return boolean
	 */
	public function Token_Exists($token)
	{
		$query = '
			SELECT 
				count(*) as cnt 
			FROM 
				condor_admin.tokens
			WHERE 
				token=\''.$this->sql->Escape_String($token).'\'
			 AND 
				company_id='.$this->company_id;
		$res = $this->sql->Query($query);
		$row = $res->Fetch_Object_Row();
		return ($row->cnt == 1);
	}
	
	/**
	 * Returns whether a list of copia documents exist.
	 * The data array has keys for the dnis number. Each
	 * data[dnis] is an array of the tiffs in there.
	 *
	 * @param Array $data
	 */
	public function Copia_File_Exists($data)
	{
		$return = Array();
		if(!is_array($data))
		{
			return FALSE;
		}
		foreach($data as $dnis=>$tiffs)
		{
			if(!isset($return[$dnis]))
			{
				$return[$dnis] = Array();
			}
			if(!is_array($tiffs)) $tiffs = array($tiffs);
			foreach($tiffs as $tiff)
			{
				$copia_file = sprintf(self::COPIA_FILE,$dnis,$tiff);
				$return[$dnis][$tiff] = file_exists($copia_file);
			}
		}
		return $return;
	}	
	
	/**
	 * Returns a Copia Document if it exists. It will convert to a PDF
	 * unless you pass the optional get_as_tiff argument
	 *
	 * @param int $dnis
	 * @param int $tiff
	 * @param int $get_as_tiff
	 * @return mixed 
	 */
	public function Get_Copia_Document($dnis,$tiff,$get_as_tiff=FALSE)
	{
		$copia_file = sprintf(self::COPIA_FILE,$dnis,$tiff);
		$data = FALSE;
		if(file_exists($copia_file))
		{
			$data = gzuncompress(file_get_contents($copia_file));
			if($get_as_tiff === FALSE)
			{
				$data = $this->Tiff_To_PDF($data);
			}
		}
		return $data;
		
	}

	/**
	 * Returns an array of templates containing a given token.
	 * Attempts to
	 *
	 * @param string $token
	 * @return mixed
	 */
	public function Get_Templates_Containing_Token($token)
	{
		$ret_val = FALSE;
		if($this->Token_Exists($token))
		{
			$query = 'SELECT 
					name,
					data 
				FROM 
					template
				WHERE 
					company_id='.$this->company_id.' 
				AND
					status=\'ACTIVE\' 
				AND 
					content_type=\'text/html\'';
			$res = $this->sql->Query($query);
			if($res->Row_Count() > 0)
			{
				$ret_val = Array();
				while($row = $res->Fetch_Object_Row())
				{
					if(strpos($row->data,$token) !== false)
					{
						$ret_val[] = $row->name;
					}
				}
				if(count($ret_val) == 0) $ret_val = false;
			}
		}
		return $ret_val;
	}

	public function Create_Token($token, $descr)
	{
		$s_token = $this->sql->Escape_String($token);
		$s_descr = $this->sql->Escape_String($descr);
	
		$query = "
			INSERT INTO
				condor_admin.tokens
			SET
				token = '$s_token',
				description = '$s_descr',
				company_id = {$this->company_id},
				date_created = NOW()
		";
		try
		{
			$this->sql->Query($query);
			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Returns the complete list of tokens available
	 * in the form of an array of stdClass objects
	 * for this company.
	 *
	 * @return array
	 */
	public function Get_Tokens()
	{
		$ret_val = FALSE;
		$query = 'SELECT
			token,
			description
			FROM condor_admin.tokens
			WHERE company_id='.$this->company_id;
		$res = $this->sql->Query($query);
		if($res->Row_Count() > 0)
		{
			$ret_val = Array();
			while($row = $res->Fetch_Object_Row())
			{
				$ret_val[] = $row;
			}
		}
		return $ret_val;
	}

	/**
	 * Renders a cover page and returns the finished result.
	 *
	 * @param array $cover_data
	 */
	private function Render_Cover_Page($cover_data)
	{
		$cover_page_data = false;
		if(isset($cover_data['template_name']) && is_string($cover_data['template_name']))
		{
			$template_name = $cover_data['template_name'];
		}
		else
		{
			$template_name = $this->Default_Cover_Page();
		}

		if($template_name)
		{
			$cover_page = new Document(
				$this->sql,
				$this->user_id,
				$this->mode,
				$template_name
			);
			$cover_page->Condor_API_Auth($this->condor_api_auth);

			$cover_page->Render($cover_data, $this->company_id);
			$cover_page_data = $cover_page->Get_Return_Object()->data;

			/*
				Now that we have the cover page data, we need to convert it to
				PostScript. How fun...
			*/
			$cover_page_data = Filter_Manager::Transform(
				$cover_page_data,
				Filter_Manager::INPUT_HTML,
				Filter_Manager::OUTPUT_PS
			);

			/*
				We then have to massage the cover page PostScript so that it includes
				the number of pages we're faxing. We'll have to look for the Condor "default"
				token of @@fax_pages@@ which is different from the standard template tokens.
			*/
			$str_pos = strpos($cover_page_data, "\n", strpos($cover_page_data, '@@fax_pages@@'));
			//This count is the page-count + 1 to account for the coverpage.
			$cover_page_data = substr_replace($cover_page_data, " /page_count_with_cp IS", $str_pos, 0);
			$cover_page_data = str_replace('@@fax_pages@@', '', $cover_page_data);

			$str_pos = strpos($cover_page_data, "\n", strpos($cover_page_data, '%%BeginResource:'));
			//just in case we need to supply more than one page as a cover page
			if(isset($cover_data['covpaglen']) && is_numeric($cover_data['covpaglen']))
			{
				$covpaglen = $cover_data['covpaglen'];
			}
			else
			{
				$covpaglen = 1;
			}
			$postscript_addtions = <<<POSTSCRIPT
%Inserted by Condor
/nullstring () def
/page_count_with_cp page-count length 2 add string def
page-count cvi $covpaglen add page_count_with_cp cvs
/IS {
    dup where {
        pop
        load dup nullstring ne {S} {pop} ifelse
    } {pop} ifelse
} bind def
POSTSCRIPT;
			$cover_page_data = substr_replace($cover_page_data, $postscript_addtions, $str_pos, 0);
			$str_pos = strpos($cover_page_data, "\n", strpos($cover_page_data, '%%EndProlog'));
			$cover_page_data = substr_replace($cover_page_data,'/new_page_count { page-count 1 add } def',$str_pos,0);
		}

		return $cover_page_data;
	}

	/**
	 * Gets the default cover page for faxing.
	 *
	 * @return string The template name that is the default fax cover page.
	 */
	private function Default_Cover_Page()
	{
		$ret_val = false;

		$query = "
			SELECT
				template_name
			FROM
				fax_cover_sheet
			WHERE
				company_id = $this->company_id";

		try
		{
			$result = $this->sql->Query($query);
		}
		catch(Exception $e)
		{
			exit($e->getMessage());
		}

		if(($row = $result->Fetch_Object_Row()))
		{
			$ret_val = $row->template_name;
		}

		return $ret_val;
	}
	/**
	 * Converts Tiff Data to PDF data.
	 * Requires The tiff2pdf program to be installed.
	 * @param string $tiff_data
	 * @return string
	 */
	private function Tiff_To_PDF($tiff_data)
	{
		$pdf = FALSE;
		//Easiest way I found to do this was to just dump
		//the data to a temp file and then call the tiff2pdf
		//on that and return the response.
		$tmp_file = tempnam('/tmp','TIFF_');
		file_put_contents($tmp_file,$tiff_data);
		if(is_file($tmp_file))
		{
			$cmd = BIN_TIFF2PDF. ' '.$tmp_file;
			$process = popen($cmd,'r');
			if(is_resource($process))
			{
				$pdf = stream_get_contents($process);
				pclose($process);
			}
			unlink($tmp_file);
		}
		return $pdf;
	}
	
	/**
	 * Attempts to set the directory to $dir. Will check
	 * if it's a mount if $is_mount is true. Also makes sure
	 * that it's readable and writeable before continueing.
	 *
	 * @param string $dir
	 * @param string $is_mount
	 * @return boolean
	 */
	private function Set_Dir($dir, $is_mount = false)
	{
		require_once(CONDOR_DIR.'scripts/check_for_mount.php');
		$ret_val = FALSE;
		if($is_mount !== true || findMount($dir) == true)
		{
			if(!is_dir($dir))
			{
				if(!mkdir($dir,0755,TRUE))
				{
					$ret_val = FALSE;
				}
			}
			if(is_readable($dir) && is_writeable($dir))
			{
				$ret_val = TRUE;
			}
			else 
			{
				$ret_val = FALSE;
			}
		}
		else 
		{
			$ret_val = FALSE;
		}
		$this->condor_dir = $ret_val === TRUE ? $dir : FALSE;
		return $ret_val;
	}

	/**
	 * Attach one or more parts to an archive id. $parts can 
	 * be an array of part_ids or just a single part_id. Each
	 * will be added as attachments to $doc_id. Returns the 
	 * number of parts successfully attached.
	 *
	 * @param int $doc_id
	 * @param mixed $parts
	 */
	public function Attach_Parts_To_Archive($doc_id, $parts)
	{
		//Basically what we have to do is create NEW parts
		//Which are clones of the ones in $parts 
		//then link THOSE to to $doc_id
		$ret_val = FALSE;
		if(!is_array($parts)) $parts = array($parts);
		if(is_numeric($doc_id) && count($parts) > 0)
		{
			$doc = new Document($this->sql, $this->user_id, $this->mode);
			$doc->Condor_API_Auth($this->Get_API_Auth());
			if($doc->Load($doc_id))
			{
				//Now we loop through the part_ids, 
				//if it's owned by this Company, 
				//We'll go ahead and copy and attach it
				$attached_parts = 0;
				foreach($parts as $part_id)
				{
					if(is_numeric($part_id))
					{
						$old_part = new Part($this->sql, $this->user_id, $this->Document_Action(),$part_id);
						if($old_part->Is_Owned_By($this->company_id))
						{
							$old_part->Load();
							$ret_obj = $old_part->Get_Return_Object();
							//Basically create a NEW part and attach that.
							$doc->Add_Email_Attachment(
								$ret_obj->content_type, 
								$ret_obj->data,
								$ret_obj->filename,
								$this->Get_Directory()
								. self::FILENAME_FORMAT 
							);
							$attached_parts++;
						}
					}
				}
				$ret_val = $attached_parts > 0 ? $attached_parts : false;
			}
		}
		return $ret_val;
	}

}	// End Condor Class
?>
