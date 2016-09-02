<?php
require_once('condor_crypt.php');
/**
 * Part class.
 *
 * @author Brian Feaver
 * @copyright Copyright 2006 The Selling Source, Inc.
 */
class Part
{
	// Resources
	private $sql;
	private $user_id;
	private $doc_action;
	private $api_auth;
	
	// Data
	private $data;
	private $part_id;
	private $parent_id;
	private $content_type;
	private $uri;
	private $filename;
	private $compression;
	private $hash;
	private $audit_status;
	private $encrypted;
		
	// Child Array
	private $children;
	
	// Audit Statuses
	const AUDIT_MISSING = 'MISSING';
	const AUDIT_MODIFIED = 'MODIFIED';
	const AUDIT_SUCCESS = 'SUCCESS';
	
	/**
	 * Class constructor.
	 *
	 * @param object $sql
	 * @param int $user_id
	 * @param int $part_id
	 * @param int $parent_id
	 * @param string $content_type
	 * @param string $uri
	 * @param string $filename
	 * @param string $compression
	 * @param string $data
	 */
	function __construct(
		&$sql,
		$user_id,
		&$doc_action,
		$part_id = NULL,
		$parent_id = NULL,
		$content_type = NULL,
		$uri = NULL,
		$filename = NULL,
		$compression = NULL,
		$data = NULL
	)
	{
		$this->sql =& $sql;
		$this->user_id = $user_id;
		$this->doc_action =& $doc_action;
		
		$this->part_id = $part_id;
		$this->parent_id = $parent_id;
		$this->content_type = $content_type;
		$this->uri = $uri;
		$this->filename = $filename;
		$this->compression = $compression;
		$this->data = $data;
		$this->hash = NULL;
		$this->audit_status = NULL;
		$this->encrypted = 0;
		
		$this->children = array();
	}
	
	/**
	 * Set whether or not to use encryption
	 * for this part.
	 *
	 * @param boolean $val
	 */
	
	public function Use_Encryption($val)
	{
		$this->encrypted = $val;
	}
	
	/**
	 * Saves this part and all its children to the database and as files.
	 *
	 * @param int $document_id
	 * @param string $file_format
	 * @param int $parent_id
	 */
	public function Save($document_id, $file_format = NULL, $parent_id = NULL)
	{
		$this->parent_id = $parent_id ? $parent_id : $this->parent_id;
		
		$this->compression = 'GZ'; // For now...
		
		// Save the root part
		$this->Insert_Part($document_id);
		$this->filename = sprintf($file_format, $document_id, $this->part_id);
		$this->Save_As_File($document_id);
		
		foreach($this->children as $child)
		{
			$child->Save($document_id, $file_format, $this->part_id);
		}
		
		return $this->part_id;
	}
	
	/**
	 * Inserts this part into the Condor database and returns the part_id.
	 *
	 * @param int $document_id
	 * @return int
	 */
	public function Insert_Part($document_id)
	{
		$hash = md5($this->data);
		
		// Let's set the URI to NULL if it doesn't exist
		$uri = $this->uri ? mysql_escape_string($this->uri) : 'NULL';
		
		//Make sure parent id is an int
		if(!is_numeric($this->parent_id)) $this->parent_id = 'NULL';
		$encrypted = $this->encrypted ? 1 : 0;
		$query = "
			INSERT INTO
				part
			SET
				date_created	= NOW(),
				parent_id		= $this->parent_id,
				content_type	= '$this->content_type',
				uri				= '$uri',
				hash			= '$hash',
				encrypted       = '$encrypted',
				date_audit		= NOW()";
		
		try
		{
			$this->sql->Query($query);
			$this->part_id = $this->sql->Insert_Id();
		}
		catch(Exception $e)
		{
			exit($e->getMessage());
		}
		
		// Insert relationship to the document
		$query = "
			INSERT INTO
				document_part
			SET
				document_id	= $document_id,
				part_id		= $this->part_id";
		
		try
		{
			$this->sql->Query($query);
		}
		catch(Exception $e)
		{
			exit($e->getMessage());
		}

		return $this->part_id;
	}
	
	/**
	 * Saves the filename to the database and writes the file to the Condor
	 * filesystem.
	 *
	 * @param string $filename
	 * @param string $compression
	 */
	private function Save_As_File($document_id)
	{
		$tries = 2;
		
		// Update the file_name field for this part
		$query = "
			UPDATE
				part
			SET
				file_name = '$this->filename',
				compression = '$this->compression'
			WHERE
				part_id = $this->part_id";
		try
		{
			$this->sql->Query($query);
			$data = $this->data;
			// Are we going to compress the data?
			if(strcasecmp($this->compression,'gz') == 0)
			{
				$data = gzcompress($data);
			}
			if($this->encrypted)
			{
				$data = Condor_Crypt::Encrypt($data);
			}
			if(empty($data))
			{
				throw new Exception("Data was empty for {$this->part_id}");
			}
			// Save the file to the Condor filesystem
			do
			{
				// attempt to write to the file system
				$written = file_put_contents($this->filename, $data);
			}
			while (--$tries && (!$written));
		
			if (!$written)
			{
				throw new Exception("Could not write data to file {$this->filename}");	
			} else {
				// if we are using a remote server, do the same there
				if(CONDOR_REMOTE_SERVER)
				{
					$connection = ssh2_connect(CONDOR_REMOTE_SERVER, CONDOR_REMOTE_PORT);
					ssh2_auth_password($connection,CONDOR_REMOTE_USER,CONDOR_REMOTE_CRED);
		
					do
					{
						// attempt to write to the file system
						$written = ssh2_scp_send($connection,$this->filename,"/condor".$this->filename);
					}
					while (--$tries && (!$written));
				
					if (!$written)
					{
						throw new Exception("Could not write data to file {$this->filename} on remote server {CONDOR_REMOTE_SERVER}");
					} else {
						// copy was successful remove local
						unlink($this->filename);
					}
				}
			}
		}
		catch (Exception $e)
		{
			$this->doc_action->Log_Action('SAVE_WRITE_ERROR', $document_id, $this->user_id);
			$this->Update_Part_Audit(self::AUDIT_MISSING);
		}
	}
	
	/**
	 * Updates the audit status of the document part.
	 *
	 * @param int $document_part_id
	 * @param string $audit_status
	 * @param int $execution_id job id if applicable
	 */
	public function Update_Part_Audit($audit_status, $execution_id = null)
	{
		$query = "
			UPDATE
				part
			SET
				date_audit = NOW(),
				audit_status = '$audit_status'
			WHERE
				part_id = $this->part_id
				and audit_status != '$audit_status'";
		
		try
		{
			$this->sql->Query($query);
		}
		catch(Exception $e)
		{
			die($e->getMessage());
		}
		
		$query = "
			INSERT INTO
				part_audit
			SET
				date_audit = NOW(),
				audit_status = '$audit_status',
				part_id = $this->part_id";
		
		if (!is_null($execution_id))
		{
			$query .= ", execution_id = $execution_id";
		}
		
		try
		{
			$this->sql->Query($query);
		}
		catch (Exception $ex)
		{
			die($ex->getMessage());
		}
		
		
	}
	
	/**
	 * Checks to see if the hash is set, 
	 * if it isn't queries the database to load it
	 * otherw wise does nothing.
	 *
	 */
	private function Get_Hash()
	{
		if(!isset($this->hash) || is_null($this->hash))
		{
			$query = "
				/* File: ".__FILE__.", Line: ".__LINE__." */
				SELECT
					hash,
					audit_status
				FROM
					part
				WHERE
					part_id = $this->part_id";
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
				$hash = $row->hash;
				$original_status = $row->audit_status;
			}
			else 
			{
				throw new Exception(__METHOD__ . " -> Invalid Part ID");
			}
			$this->hash = $row->hash;
			$this->audit_status = $row->audit_status;
		}
	}
	/**
	 * Audits this part and updates its status in the parts db
	 * @return string audit status
	 */
	public function Audit($execution_id = null)
	{
		$this->Get_Hash();
		$hash = $this->hash;
		$original_status = $this->audit_status;
		if (file_exists($this->filename))
		{
			// data should already be loaded:
			$curr_hash = md5($this->data);
			
			if ($curr_hash != $hash)
			{
				$status = self::AUDIT_MODIFIED;
			}
			else 
			{
				$status = self::AUDIT_SUCCESS;
			}
		}
		else 
		{
			$status = self::AUDIT_MISSING;
		}

		$this->Update_Part_Audit($status, $execution_id);
		
		return $status;
	}
	
	/**
	 * Loads the part from the Condor database.
	 */
	public function Load()
	{
		$query = "
			/* ".__METHOD__." */
			SELECT
				parent_id,
				content_type,
				uri,
				file_name,
				compression,
				encrypted		
			FROM
				part
			WHERE
				part_id = $this->part_id";
		
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
			$this->Load_From_Row($row);
		}
		
		$this->Load_File();
		
		$this->Load_Children();
	}
	
	/**
	 * Essentially load the stuff from an existing row since
	 * things like the file system audit were in reality loading it twice
	 * we'll try this
	 * 	
	 * 
	 * private $data;
	private $part_id;
	private $parent_id;
	private $content_type;
	private $uri;
	private $filename;
	private $compression;
	 *
	 * @param unknown_type $row
	 */
	public function Load_From_Row($row)
	{
		if(isset($row->parent_id))
		{
			$this->parent_id = $row->parent_id;
		}
		if(isset($row->part_id))
		{
			$this->part_id = $row->part_id;
		}
		if(isset($row->content_type))
		{
			$this->content_type = $row->content_type;
		}
		if(isset($row->uri))
		{
			$this->uri = $row->uri == 'NULL' ? null : $row->uri;
		}
		if(isset($row->file_name))
		{
			$this->filename = $row->file_name;
		}
		if(isset($row->compression))
		{
			$this->compression = $row->compression;
		}
		if(isset($row->hash))
		{
			$this->hash = $hash;
		}
		if(isset($row->audit_status))
		{
			$this->audit_status = $row->audit_status;
		}
		if(isset($row->encrypted))
		{
			$this->encrypted = $row->encrypted;
		}
	}
	
	/**
	 * Loads this part's children from the Condor database.
	 *
	 */
	public function Load_Children()
	{
		
		$query = "
			/* ".__METHOD__." */
			SELECT
				part_id,
				parent_id,
				content_type,
				uri,
				file_name,
				compression,
				encrypted
			FROM
				part
			WHERE
				parent_id = $this->part_id";
		
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
			
			$new_part = new Part(
				$this->sql,
				$this->user_id,
				$this->doc_action,
				$row->part_id,
				$row->parent_id,
				$row->content_type,
				$row->uri,
				$row->file_name,
				$row->compression
			);
			$new_part->Use_Encryption($row->encrypted);
			$new_part->Load_File(); // Get the data
			
			$this->children[] = $new_part;
			
		}
		
		// Load children's children
		foreach($this->children as $child)
		{
			$child->Load_Children();
		}
		
	}

	/**
	 * Tests to see if the local directory for a filename.
	 * It will create the directory if it doesn't already exist.
	 *
	 * @return string The directory to write this file into.
	 */
	public function testDir($filename)
	{
		$dir_ary = explode("/",$filename);
		unset($dir_ary[count($dir_ary)-1]);
		$dir = implode("/",$dir_ary);

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

		return $ret_val;
	}

	/**
	 * Loads the file based on the Part's filename.
	 *
	 */
	public function Load_File()
	{
		// if we are using a remote server, go get the data
		if(CONDOR_REMOTE_SERVER)
		{
			$connection = ssh2_connect(CONDOR_REMOTE_SERVER, CONDOR_REMOTE_PORT);
			ssh2_auth_password($connection,CONDOR_REMOTE_USER,CONDOR_REMOTE_CRED);
		
			$this->testDir($this->filename);
			
			ssh2_scp_recv($connection,"/condor".$this->filename,$this->filename);
			$this->data = file_get_contents($this->filename);
			unlink($this->filename);
			if (!$this->data)
			{
				throw new Exception("Could not read data from remote file {$this->filename} on remote server {CONDOR_REMOTE_SERVER}");
			}
		} else {
			if(is_file($this->filename))
			{
				$this->data = file_get_contents($this->filename);
			} else {
				throw new Exception("Could not read data from file {$this->filename}");
			}
		}
		
		if ($this->data) {
			if($this->encrypted)
			{
				$this->data = Condor_Crypt::Decrypt($this->data);
			}
			if ($this->compression === 'GZ')
			{
				// suppress the output because php sucks.
				$this->data = @gzuncompress($this->data);
			}
		}
	}
	
	/**
	 * Renders the part and it's attachments as a PDF.
	 * 
	 * You can always add attachments/children to the part after it's been renderred.
	 *
	 * @param string $mode
	 * @param bool $save_children
	 */
	public function Render_As_PDF($mode = 'LIVE', $save_children = FALSE)
	{
		$this->Render_As_Other_File_Format(Filter_Manager::OUTPUT_PDF, $mode, $save_children);
	}
	
	/**
	 * Renders the part as a PostScript file.
	 *
	 * @param string $mode
	 * @param bool $save_children
	 */
	public function Render_As_PostScript($mode = 'LIVE', $save_children = false)
	{
		$this->Render_As_Other_File_Format(Filter_Manager::OUTPUT_PS, $mode, $save_children);
	}
	
	/**
	 * This function renders the document as some other file format. Since
	 * originally we only had to render documents as PDF, we created the
	 * Render_As_PDF() function. Since now we need to do both PDF and
	 * PostScript, this function will take the part to be rendered
	 * and convert it based on the $type.
	 *
	 * @param string $type
	 */
	private function Render_As_Other_File_Format($type, $mode = 'LIVE', $save_children = FALSE)
	{
		assert('isset($this->api_auth)');
		
		foreach($this->children as $child)
		{
			
			// Replace any URI's in data with the condor script
			switch($mode)
			{
				case MODE_DEV:
					$host = 'condor.4.edataserver.com.gambit.tss:8080';
					break;
				case MODE_RC:
					$host = 'rc.condor.4.edataserver.com';
					break;
				case MODE_LIVE:
					$host = "condor.4.edataserver.com";
					break;
			}
			
			$url = "http://$this->api_auth@{$host}/view_part.php?p={$child->Get_Part_ID()}";
			
			$this->data = str_replace($child->Get_URI(), $url, $this->data);
			
			if($child->Get_Content_Type() == CONTENT_TYPE_TEXT_HTML)
			{
				$child->Render_As_Other_File_Format($type, $mode, $save_children);
			}
			
		}
		
		$pdf = Filter_Manager::Transform(
			$this->data,
			Filter_Manager::INPUT_HTML,
			$type
		);
		
		// Let's not overwrite the rendered document unless the PDF was successful
		if($pdf !== FALSE)
		{
			
			$this->data = $pdf;
			
			$this->content_type = CONTENT_TYPE_APPLICATION_PDF;
			
			if(!$save_children)
			{
				// We don't need the file attachments anymore, so let's dump them.
				$this->children = array();
			}
			
		}
	}
	
	/**
	 * Returns a stdClass object based on the this part and its children. The object has
	 * the following public variables:
	 * 
	 * 1. data - The content of the part
	 * 2. uri - The uri of the part
	 * 3. content_type - The content-type of the part
	 * 4. attached_data - an array of additional part return objects
	 *
	 * @return object
	 */
	public function Get_Return_Object()
	{
		$return_obj = new stdClass();
		
		$return_obj->part_id = $this->part_id;
		$return_obj->data = $this->data;
		$return_obj->uri = $this->uri;
		$return_obj->content_type = $this->content_type;
		$return_obj->attached_data = array();
		$return_obj->encrypted = $this->encrypted ? '1' : '0';
		
		foreach($this->children as $child)
		{
			$return_obj->attached_data[] = $child->Get_Return_Object();
		}
		
		return $return_obj;
	}
	
	/**
	 * Sets the data.
	 *
	 * @param string $data
	 */
	public function Set_Data($data)
	{
		$this->data = $data;
	}
	
	/**
	 * Sets the content_type.
	 *
	 * @param string $content_type
	 */
	public function Set_Content_Type($content_type)
	{
		$this->content_type = $content_type;
	}
	
	/**
	 * Sets the URI.
	 *
	 * @param string $uri
	 */
	public function Set_URI($uri)
	{
		$this->uri = $uri;
	}
	
	/**
	 * Returns the part ID.
	 *
	 * @return int
	 */
	public function Get_Part_ID()
	{
		return intval($this->part_id);
	}
	
	/**
	 * Returns the URI.
	 *
	 * @return string
	 */
	public function Get_URI()
	{
		return $this->uri;
	}
	
	/**
	 * Returns the content-type.
	 *
	 * @return string
	 */
	public function Get_Content_Type()
	{
		return $this->content_type;
	}
	
	/**
	 * Adds a child to the part.
	 *
	 * @param object $child
	 */
	public function Add_Child(Part $child)
	{
		$this->children[] = $child;
	}
	
	/**
	 * Sets and retrieves the Condor API Authentication string.
	 *
	 * @param string $api_auth
	 */
	public function Condor_API_Auth($api_auth = NULL)
	{
		if(!is_null($api_auth)) $this->api_auth = $api_auth;
		return $this->api_auth;
	}
	
	public function Is_Owned_By($company_id)
	{
		$ret_val = false;
		if(is_numeric($this->part_id) && is_numeric($company_id))
		{
			$query = 'SELECT
				agent.company_id as company_id
			FROM
				part
			LEFT JOIN 
				document_part ON document_part.part_id = part.part_id
			LEFT JOIN 
				document ON document.document_id = document_part.document_id
			LEFT JOIN 
				condor_admin.agent ON document.user_id = agent.agent_id
				WHERE part.part_id = '.$this->part_id;
			try 
			{
				$res = $this->sql->Query($query);
			}
			catch (Exception $e)
			{
				
			}
			//Loop through any documents that had this part
			//and see if it was owned by $company_id
			while($row = $res->Fetch_Object_Row())
			{
				$ret_val = ($row->company_id == $company_id);
				if($ret_val) break;
			}
		}
		return $ret_val;
	}
}
?>
