<?PHP
/*
	@public
	
	@version
		3.0.0 7/2005 Randy Kochis

*/

class Condor extends Prpc_Server
{
	var $sql;
	var $database;
		
	/**
	* @return bool
	* @param $use_pr_Apc bool
	* @param $database string
	* @desc Constructor, setup Prpc_Server & set the global MySQL object.
	*/
	function Condor($use_prpc = TRUE)
	{
		global $sql;
		
		$this->condor_content;
		$this->sql = $sql;
		$this->database = MYSQL_DB;
		if($use_prpc)
		{
			//parent::Prpc_Server();	// Do this last in the constructor or variables will not be set
			parent::__construct();
		}
	}
	
	/**
	* @return string
	* @desc Pulls up the legal docs 		
 	*/
	public function View_Legal_Doc ($application_id)
	{
		$query = "SELECT document FROM document_archive 
			JOIN 
				signature ON (signature.document_archive_id = document_archive.document_archive_id) 
			AND 
				signature.application_id = $application_id 
			ORDER by signature_id DESC 
			LIMIT 1";
		
		$result = $this->sql->Query (MYSQL_DB, $query);
		
		// If the query finds a row
		if($this->sql->Row_Count($result) > 0) 
		{
			$data_set = $this->sql->Fetch_Object_Row($result);
		}
		if(!$data_set->document)
		{
			return FALSE;
		}
		else
		{
			return gzuncompress($data_set->document);
		}
	}
	
	/**
	* @return string
	* @desc Used to verify that Prpc is working correctly		
 	*/
	function Test_Condor_Prpc()
	{
		$test_var = "Condor Parent Prpc Is Working...";
		return $test_var;
	}
	
	
	/**
	* @return string
	* @param $field,$type
	* @desc Process the incoming request of legal document -- This will change when we use the templates
	*/
	function Display ($type, $field)
	{
		switch ($type)
		{
			case "phone":
			$field = "(".substr($field,0,3).")".substr($field,3,3)."-".substr($field,6,4);
			break;

			case "ssn":
			$field = substr($field,0,3)."-".substr($field,3,2)."-".substr($field,5,4);
			break;

			case "date":
			$matches = preg_split("/\//",$field);
			if (!$matches[2])
				$matches = preg_split("/\-/",$field);
			$field = "$matches[1]/$matches[2]/$matches[0]";
			break;

			case "string":
			$field = ucwords($field);
			break;
			
			case "money":
			$field = sprintf ("%0.2f", $field);
			break;
					
			case "upper case":
			$field = strtoupper ($field);
			break;

			case "email":
			case "lower case":
			$field = strtolower ($field);
			break;

			case "smart case":
			$field = ucwords (strtolower ($field));
			break;
		}
		return $field;
	}
	
	/**
	* @return bool
	* @param $request array
	* @desc Process the incoming request
	*/
	function Condor_Request($request, $condor_content = '')
	{

		$this->condor_content = $condor_content;
		$this->request = $request;

		if (!isset($this->request['condor']->type) && isset($this->request ))
		{
			$this->request['condor'] = $this->request;
		}

		// use for now until we can switch transaction_id over to app_id in olp //
		//$this->request['application_id'] = $this->request['transaction_id'];
		switch($this->request['condor']['type'])
		{
			case "signature_request":

			// Creation Process
			$this->Audit_Trail('insert','signature_request_received'); //
			$this->Create_Legal_Document(); //
			$this->Create_Document_Checksum(); //
			$this->Archive_Document(); //
			$this->Audit_Trail('update','legal_document_saved'); //
			$this->signature_key = md5($this->audit_trail_id);
			$this->Write_Signature(); //
			$this->Audit_Trail('update','signature_record_saved'); //
			$this->Create_Response(); //
			return $this->condor_response; //
			break;

			case "signature_response":
			$this->audit_trail_id = $this->request['condor']['audit_trail_id'];

			// Validate the incoming response
			$this->Audit_Trail('update','signature_response_received');

			if($this->request['condor']['signature_response'] == 'TRUE')
			{
				$this->Audit_Trail('update','signature_agree');
			}
			else
			{
				$this->Audit_Trail('update','signature_disagree');
			}
			return;
			break;
		}
	}
	
	/**
	* @return bool
	* @desc Pull legal documents from the database and populate them with the values from the request array
	*/
	function Create_Legal_Document()
	{
		$query = "
			SELECT
				legal_document_id,
				document_path,
				document_name
			FROM
				legal_document
			WHERE
				property_short = 'ENT'
			AND
				legal_document_name = 'paperless_application'";
		
		$result = $this->sql->Query ($this->database, $query);

		// If the query finds a row
		if($this->sql->Row_Count($result) > 0) 
		{
			// use document data that was passed in
			$condor_content = $this->condor_content;
			// Assign the query data to this data set
			$data_set = $this->sql->Fetch_Object_Row($result);

			// Add the document ID used to the array of usable data
			$this->request['condor']['legal_document_id'] = $data_set->legal_document_id;

			if(!file_exists($data_set->document_path.$data_set->document_name))
			{
				$this->Throw_Error($this->request,4);
			}
			$this->legal_content .= "\n<!-- CONDOR SIGNATURE KEY: ".$this->Create_Signature_Key()."-->\n";

			ob_start();
			// grab document
			include_once($data_set->document_path.$data_set->document_name);
			$this->legal_content .= ob_get_clean();
			$this->legal_content .= "\n<!-- CONDOR SIGNATURE KEY: ".$this->signature_key."-->\n";
		}
		else
		{
			$this->Throw_Error($this->request,2);
		}

		return TRUE;
	}
	
	/**
	* @return bool
	* @desc Create the signature
	*/
	function Create_Signature_Key()
	{
		$this->signature_key = md5($this->audit_trail_id);	
		return $this->signature_key;
	}
	
	/**
	* @return bool
	* @desc Create a checksum for the created legal document
	*/
	function Create_Document_Checksum()
	{
		$this->checksum = md5($this->legal_content);
		return TRUE;
	}
	
	/**
	* @return bool
	* @desc Insert the created document into the database for storage
	*/
	function Archive_Document()
	{
		//$gz_legal = gzcompress($this->legal_content,5);
		$query = "
			INSERT INTO
				document_archive
				(legal_document_id, document, date_created)
			VALUES
				(".$this->request['condor']['legal_document_id'].", '".mysql_escape_string(gzcompress($this->legal_content,5))."', NOW())";

		$this->sql->Query ($this->database, $query);
		$this->archive_id = $this->sql->Insert_Id();
		
		return TRUE;
	}
	
	/**
	* @return bool
	* @param $type string
	* @param $field string
	* @desc Keep track via an audit trail of what is happening and timestamp the event
	*/
	function Audit_Trail($type, $field)
	{
		switch($type)
		{
			case "insert":
			$query = "
				INSERT INTO
					audit_trail
					(".$field.", date_created)
				VALUES
					(NOW(), NOW())";
			
			$this->sql->Query ($this->database, $query);
			$this->audit_trail_id = $this->sql->Insert_Id();
			break;
			
			case "update":
			$query = "
				UPDATE
					audit_trail
				SET
					".$field." = NOW()
				WHERE
					audit_trail_id = ".$this->audit_trail_id."";
			$result = $this->sql->Query ($this->database, $query);
			
			if($field == "signature_record_saved")
			{
				$query = "
					UPDATE
						audit_trail
					SET
						signature_id = '".$this->signature_id."'
					WHERE
						audit_trail_id = ".$this->audit_trail_id."";
				
				$result = $this->sql->Query ($this->database, $query);
			}
			break;
		}
		return;
	}
	
	/**
	* @return bool
	* @desc Insert the created signature into the database
	*/
	function Write_Signature()
	{
		// set this so we don't throw an exception if we don't have a transaction_id
		if (!$this->request['application_id'])
		{
			$this->request['application_id']  = "99999";
		}
		$query = "
			INSERT INTO
				signature
				(
					application_id,
					document_archive_id, 
					document_checksum, 
					property_short, 
					signature_key, 
					ip_address,
					date_created
				)
			VALUES
				(
					".$this->request['application_id'].",
					".$this->archive_id.",
					'".$this->checksum."',
					'".$this->request['config']->property_short."',
					'".$this->signature_key."', 
					'".$this->request['data']['client_ip_address']."',
					 NOW()
				 )";
		
			$result = $this->sql->Query ($this->database, $query);
			$this->signature_id = $this->sql->Insert_Id();
			
		return TRUE;			
	}
	
	/**
	* @return bool
	* @param 
	* @desc Build the response object to be sent back.
	*/
	function Create_Response()
	{
		// Package the condor response
		
		$this->condor_response = new stdClass();
		$this->condor_response->signature_id = $this->signature_id;
		$this->condor_response->audit_trail_id = $this->audit_trail_id;
		$this->condor_response->archive_id = $this->archive_id;
		$this->condor_response->signature_key = $this->signature_key;
		$this->condor_response->property_short = $this->request['config']->property_short;
		$this->condor_response->application_id = $this->request['application_id'];

		return TRUE;
	}
		
	/**
	* @return string
	* @param $legal_document_name
	* @desc returns path to legal doc
	*/
	
	function Get_Legal_Doc($legal_document_name)
	{
	
	$query = "SELECT document_path,document_name 
			from legal_document 
			WHERE property_short='ENT' 
			AND legal_document_name='{$legal_document_name}'";

		try
		{
			$result = $this->sql->Query ($this->database, $query);
		}
		catch( MYSQL_Exception $e )
		{
			return FALSE;
		}
		
		$legal_document = $this->sql->Fetch_Object_Row($result);
	
		//die($legal_document->document_path . $legal_document->document_name);
		return $legal_document->document_path . $legal_document->document_name;

	}
		
	/**
	* @return bool
	* @param $data 
	* @param $error int
	* @desc Return and error and the data associated with it
	*/
	function Throw_Error($data, $error)
	{
		switch($error)
		{
			case 1:
			$this->condor_response->error->message = "Invalid Site Id";
			$this->condor_response->error->data = $data;
			break;
			
			case 2:
			$this->condor_response->error->message = "Invalid Document Request";
			$this->condor_response->error->data = $data;
			break;
			
			case 3:
			$this->condor_response->error->message = "Invalid Condor Request";
			$this->condor_response->error->data = $data;
			break;
		}
		return TRUE;
	}
	
	/**
		View Archive Document
		
		This for just while we test 
	*/
	public function View_Archive_Document($application_id)
	{
		$valid = TRUE;
		
		$query = 'SELECT * FROM signature S, document_archive A WHERE S.document_archive_id = A.document_archive_id AND S.application_id = "'. $application_id.'" ORDER BY A.date_created DESC';

		try
		{
			$result = $this->sql->Query ($this->database, $query);
			
			$row_object = $this->sql->Fetch_Object_Row($result);
			
			if($row_object != false)
			{
				$document = gzuncompress($row_object->document);
			}
			else 
			{
				$document = 'No document found';
			}
		}
		catch (Exception $e)
		{
			$valid = FALSE;
			$error = $e->getMessage();
		}

		if($valid)
		{
			$return_var = $document;
		}
		else 
		{
			$return_var = $error;
		}

		return $return_var;
	}
}
?>
