<?php

/**
	@version:
			1.0.0 2005-04-05 - Condor Class Module for BFW
	@author:	
			Randy Kochis - version 1.0.0
	@Updates:	
	
	@Todo:  
*/

// require lib5 prpc client.php
require_once ('prpc/client.php');

// set config vars accept condor_server as input

class Condor_Client
{

	public $condor;
	public $response;

	function __construct() {

		$this->condor = new Prpc_Client(CONDOR_SERVER);
	}


	function Test_Condor_Prpc()
	{
		$this->response = $this->condor->Test_Condor_Prpc();
	}


	// abstracted from fcp to use as a global olp function
	public function Condor_Get_Docs( $type, $legal_status = 'FALSE', $condor_content)
	{

		switch($type)
		{
			case "signature_request":

			// build array to send to condor_request
			$myrequest = array();
			
			// flag to insert esig (first and last name) into document for archival
			$condor_content['esignature'] = TRUE;

			$myrequest['type'] = $type;
			
			//$myrequest['config'] = $_SESSION['config'];

			// changes 8/22 since we already pass in trans_id and prop short just use them in $myrequest
			$myrequest['application_id'] = $condor_content['application_id'];
			$myrequest['config']->property_short = strtoupper($condor_content['config']->property_short);
			
		/*	// for completed applications
			if ( isset($_SESSION['blackbox']['winner']) )
			{
				$myrequest['transaction_id'] = $_SESSION['transaction_id'];
				// pass in bb winner as property short
				$myrequest['config']->property_short = strtoupper($_SESSION["blackbox"]["winner"]);
			}
			elseif (isset($_SESSION['cs']['transaction_id'])) // for customer service set from cs data
			{
				$myrequest['transaction_id'] = $_SESSION['cs']['transaction_id'];
				$myrequest['config']->property_short = strtoupper($_SESSION['config']->bb_force_winner);
			}
			else // react
			{
				$myrequest['transaction_id'] = $_SESSION['transaction_id'];
				$myrequest['config']->property_short = strtoupper($_SESSION['config']->bb_force_winner);
			}*/

			// limit ip to 25 chars - sometimes we get more than the ip address
			if(!empty($_SESSION['data']['client_ip_address']))
			{
				$myrequest['data']['client_ip_address'] = substr($_SESSION['data']['client_ip_address'],0,24);
			}
			else
			{
				$myrequest['data']['client_ip_address'] = substr($condor_content['ip_address'],0,24);
			}

			try
			{
				$condor_response = $this->condor->Condor_Request($myrequest, $condor_content);

			}
			catch (Exception $e)
			{
				$applog = new Applog(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, 'Condor Client Class', APPLOG_ROTATE);
				$applog->Write( 'ERROR: Condor ' . $myrequest['type'] . ' Session_id: ' . session_id() );
				$applog->Write('Exception ' . $e->getMessage(), LOG_ERR);
			}

			$document = $condor_response;

			// returns array that goes into the session
			$this->response = $document;

			break;

			case "signature_response":

			// need to pass in array not an object!
			// build array to send to condor - it won't accept stdclass objects
			$myrequest = array();
			$myrequest['condor']['type'] ='signature_response';
			$myrequest['condor']['signature_response']= $legal_status;
			$myrequest['condor']['audit_trail_id'] = $_SESSION['condor']->audit_trail_id;

			// set class response var
			$this->response = $this->condor->Condor_Request($myrequest);

			break;
		}
		return TRUE;
	}

	// pass in document you want and the content to fill it in
	public function Preview_Docs($legal_doc_name, $condor_content)
	{
		$legal_document = $this->condor->Get_Legal_Doc($legal_doc_name);
		ob_start();
		include($legal_document);
		$document .= ob_get_clean();
		return $document;
	}

	/**
	* @param  $application_id 
	* @desc pull up archived document from the database and display to user
	**/

	function View_Legal ($application_id)
	{

	
		$document = $this->condor->View_Legal_Doc($application_id);

		if(!$document)
		{
			return FALSE;
		}
		else
		{
			return $document;
		}
		
		/*$query = "SELECT document FROM document_archive 
			JOIN 
				signature ON (signature.document_archive_id = document_archive.document_archive_id) 
			AND 
				signature.application_id = $application_id 
			ORDER by signature_id DESC 
			LIMIT 1";
		
		$result = $sql->Query ('condor', $query);
		
		// If the query finds a row
		if($sql->Row_Count($result) > 0) 
		{
			$data_set = $sql->Fetch_Object_Row($result);
		}
		if(!$data_set->document)
		{
			//return "DOCUMENT DOES NOT EXIST!";
			return FALSE;
		}
		else
		{
			return gzuncompress($data_set->document);
		}*/
	}

	/**
	* @param $type string
	* @param $field string
	* @desc function to handle formatting used in Preview_Docs
	**/

	public function Display ($type, $field)
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
			//			$field = substr($field, 5,2)."-".substr($field, -2,2)."-".substr($field,0,4);
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

}

?>
