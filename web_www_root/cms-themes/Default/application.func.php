<?php

    function getCurrentLanguage(){
        return lum_getCurrentLanguage() == '' ? lum_isDefaultLanguage(): lum_getCurrentLanguage();
    }
    
    function getSoapClient(){
ini_set("soap.wsdl_cache_enabled", 0);
        // standard soap client for application service
	$post_url = lum_getString("[CAMPAIGN_POST_URL]").
            "?enterprise=".lum_getString("[CAMPAIGN_ENTERPRISE]").
            "&company=".lum_getString("[CAMPAIGN_COMPANY]");

	$options = array(
            'login' => lum_getString("[CAMPAIGN_POST_ID]"),
            'password' => lum_getString("[CAMPAIGN_POST_LC]"),
	);
	$client = new SoapClient($post_url."&wsdl",$options);
        return $client;
    }
 
    function CheckSoapForError($vapi_response,$error_page = 'system_error'){
        $soap_response = new StdClass();
        $soap_response->signature = new StdClass();
        $soap_response->content = new StdClass();
        $soap_response->content->section = new StdClass();
        $soap_response->errors = new StdClass();
        if (!isset($vapi_response->outcome)){
            $soap_response->signature->data = 'system_error';
            $soap_response->errors->data = 'SOAP system error. ';
        } elseif ($vapi_response->outcome == 1) {
            $soap_response->signature->data = 'success';
            $soap_response->result = $vapi_response->result;
        } else {
            $soap_response->signature->data = 'app_error';
            $soap_response->errors->data = "An error occurred processing the requested application. \n".
                    (is_array($vapi_response->error) ? implode("\n", $vapi_response->error) : $vapi_response->error);
        }
	
	switch($soap_response->signature->data){
            case 'success':
                return $soap_response;
            case 'app_error':
            case 'system_error':
                if(!isset($_SESSION)) session_start();
                $_SESSION['error_code'] = $soap_response->errors->data;
                lum_redirect("/".getCurrentLanguage()."/".$error_page);
                break;
            default:
                if(!isset($_SESSION)) session_start();
                $_SESSION['error_code'] = 'Unknown soap resonse error';
                lum_redirect("/".getCurrentLanguage()."/".$error_page);
                break;
	}
        return $soap_response;
    }
   
    function BuildGenericPageResponse($vapi_response){
        $soap_response = new StdClass();
        $soap_response->signature = new StdClass();
        $soap_response->content = new StdClass();
        $soap_response->content->section = new StdClass();
        $soap_response->errors = new StdClass();

        if (!isset($vapi_response->outcome)){
            $soap_response->signature->data = 'system_error';
            $soap_response->errors->data = 'SOAP system error. ';
        } elseif ($vapi_response->outcome == 1) {
            $soap_response->signature->data = 'success';
	    $soap_response->result = $vapi_response->result;
        } else {
            $soap_response->signature->data = 'app_error';
            $soap_response->errors->data = "An error occurred processing the requested application. \n".
                (is_array($vapi_response->error) ? implode("\n", $vapi_response->error) : $vapi_response->error);
        }
        return $soap_response;
    }

    function BuildSoapApplication($vapi_response,$status_response,$trans_response) {
        $soap_response = new StdClass();
        $soap_response->signature = new StdClass();
        $soap_response->content = new StdClass();
        $soap_response->content->section = new StdClass();
        $soap_response->errors = new StdClass();
    
        if (!isset($vapi_response->outcome)){
            $soap_response->signature->data = 'system_error';
            $soap_response->errors->data = 'Application SOAP system error. ';
        } elseif ($vapi_response->outcome == 1) {
            $soap_response->application = $vapi_response->result;
            if ($status_response->outcome == 1) {
                $soap_response->application['status'] = $status_response->result['status'];
                if ($trans_response->outcome == 1) {
                    $soap_response->signature->data = 'success';
                    $soap_response->application['transactions'] = $trans_response->result['transactions'];
                } else {
                    $soap_response->signature->data = 'system_error';
                    $soap_response->errors->data = 'Transaction SOAP system error. '.				
                       (is_array($trans_response->error) ? implode("\n", $trans_response->error) : $trans_response->error);
                }
            } else {
                $soap_response->signature->data = 'system_error';
                $soap_response->errors->data = 'Status SOAP system error. '.			
                    (is_array($status_response->error) ? implode("\n", $status_response->error) : $status_response->error);
            }
        } else {
            $soap_response->signature->data = 'app_error';
            $soap_response->errors->data = "An error occurred processing the requested application. \n".
                (is_array($vapi_response->error) ? implode("\n", $vapi_response->error) : $vapi_response->error);
        }
        return $soap_response;
    }
    
    function setSession($application){
        // set the session and cookie for expiration
        $name = lum_getString("[SESSION_NAME]");
        session_name($name);
	if (!isset($_SESSION)) session_start();
        if (lum_getString("[SESSION_TIME_LIMIT_MINUTES]")>=0) setcookie($name,session_id(),time()+(60*lum_getString("[SESSION_TIME_LIMIT_MINUTES]")));
        else setcookie($name,session_id());
        $_SESSION['application'] = $application;
    }

    function getApplication($application_id) {
	
	$client = getSoapClient();
	
	$application_xml = $client->getApplicationData($application_id);
	$status_xml = $client->getStatus($application_id);
	$transaction_xml = $client->getTransactions($application_id);
	
	$response = BuildSoapApplication($application_xml,$status_xml,$transaction_xml);
	
        CheckResponseForError($response);

        return $_SESSION['application'] = $response->application;
    }

    function getApplicationFields($application_id) {
	
	$client = getSoapClient();
	
	$fields_xml = $client->getContactFields($application_id);
	
	$response = BuildGenericPageResponse($fields_xml);
	
        CheckResponseForError($response);
        
        if(!isset($_SESSION)) session_start();

        return $response->result;
    }

    function setLoginLock($application_id) {
	
	$client = getSoapClient();
	
	$fields_xml = $client->LoginLock($application_id,'set');
	
	$response = BuildGenericPageResponse($fields_xml);
	
        CheckResponseForError($response);

        return $response->result;
    }

    function checkLoginLock($application_id) {
	
	$client = getSoapClient();
	
	$fields_xml = $client->LoginLock($application_id,'check');
	
	$response = BuildGenericPageResponse($fields_xml);
	
        CheckResponseForError($response);
	
	if ($response->result[0] != "pass") lum_redirect("/".getCurrentLanguage()."/login_locked");

        return true;
    }
    
    function CheckResponseForError($result,$error_page = 'system_error'){
	
	switch($result->signature->data){
		case 'success':
			break;
		case 'app_declined':
			lum_redirect("/".getCurrentLanguage()."/loan_not_available");
                        break;
		case 'app_error':
		case 'system_error':
			if(!isset($_SESSION)) session_start();
			$_SESSION['error_code'] = $result->errors->data;
			lum_redirect("/".getCurrentLanguage()."/".$error_page);
			break;
		default:
			if(!isset($_SESSION)) session_start();
			$_SESSION['error_code'] = 'Unknown soap resonse error:  '.$result->signature->data;
			lum_redirect("/".getCurrentLanguage()."/".$error_page);
			break;
        }
    }
?>
