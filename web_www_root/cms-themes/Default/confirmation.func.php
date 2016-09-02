<?php
    include(TEMPLATES_PATH.'application.func.php');
    
    function invalidStatusRedirect($application) {
        // Make sure the loan is in the (disagree, prospect confirmed, confimed decline, pending and declined) statuses
        if (!in_array($application['status'],
                array('disagree::prospect::*root',
                'confirmed::prospect::*root',
                'confirm_declined::prospect::*root',
                'pending::prospect::*root',
                'declined::prospect::*root',
                'agree::prospect::*root'))) {

            // send those in a contact status to the appropriate page
            if (in_array($application['status'],
                    array('queued::verification::applicant::*root',
                    'queued::underwriting::applicant::*root',
                    'follow_up::verification::applicant::*root',
                    'follow_up::underwriting::applicant::*root',
                    'in_process::prospect::*root'))) {
                lum_redirect("/".lum_getCurrentLanguage()."/more_info");
            }

            // send those in a withdraws/expired status to the appropriate page
            if (in_array($application['status'],
                         array('withdrawn::applicant::*root',
                               'expired::prospect::*root'))){
                lum_redirect("/".lum_getCurrentLanguage()."/withdrawn");
            }

            // send those in a pre-funded status to the appropriate page
            if ($application['status'] == 'approved::servicing::customer::*root'){
                lum_redirect("/".lum_getCurrentLanguage()."/complete");
            }

            // send those in a denied status to the appropriate page
            if ($application['status'] == 'denied::applicant::*root'){
                lum_redirect("/".lum_getCurrentLanguage()."/loan_not_available");
            }

            // else old link and error out
            $_SESSION['error_code'] = 'Application status redirect error.';
            lum_redirect("/".lum_getCurrentLanguage()."/system_error");
        }
    }
    
    function missingDataRedirect($application){
        // make sure data is available
        if (!isset($application) || !isset($application['application_id']) || !isset($application['status'])) {
            $_SESSION['error_code'] = 'Application missing redirect error.';
            lum_redirect("/".getCurrentLanguage()."/system_error");
        }
    }

    function BuildSoapLocalResponse($vapi_response) {
        $soap_response = new StdClass();
        $soap_response->signature = new StdClass();
        $soap_response->content = new StdClass();
        $soap_response->content->section = new StdClass();
        $soap_response->errors = new StdClass();

        if (!isset($vapi_response->outcome)){
            $soap_response->signature->data = 'system_error';
            $soap_response->errors->data = 'Application SOAP system error. ';
        } elseif ($vapi_response->outcome == 1) {
            $soap_response->result = $vapi_response->result;
            $soap_response->signature->data = 'success';
            $soap_response->page_name = $vapi_response->result->page_name;
        } else {
            $soap_response->signature->data = 'app_error';
            $soap_response->errors->data = "An error occurred processing the requested application. \n".
                    (is_array($vapi_response->error) ? implode("\n", $vapi_response->error) : $vapi_response->error);
        }
        return $soap_response;
    }
        
    function setCurrentPage($ap_id,$this_page_name){
        
        $request = $ap_id;
	
	$client = getSoapClient();
	
	$result_xml = $client->getPage($request);
	
	$response = BuildSoapLocalResponse($result_xml);
	
        CheckResponseForError($response);
        $page_result = $response->result;
 
        $page_name_map = array(
            'ent_online_confirm'        => array('esig_confirm_amount' =>true,
                                                 'esig_confirm_documents' =>true),
            'ent_online_confirm_legal'  => array('esig_confirm_documents' =>true),
            'ent_thankyou'              => array('esig_confirm_complete' =>true),
            'app_declined'              => array('esig_confirm_decline' =>true)
        );
         
        //redirect to the proper page
        if (isset($page_name_map[$page_result->page_name])){
            if (!isset($page_name_map[$page_result->page_name][$this_page_name])) {
                $lookup = array_keys($page_name_map[$page_result->page_name]);
                lum_redirect("/".getCurrentLanguage()."/".$lookup[0]);
            }
        } else {
            $_SESSION['error_code'] = 'Unknown page: '.$page_result->page_name.' error';
            lum_redirect("/".getCurrentLanguage()."/system_error");
        }
        return $page_result;
    }
?>
