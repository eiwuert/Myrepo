<?php
    include(TEMPLATES_PATH.'application.func.php');
    
    function getSession(){
        // get the session, or reset it from a valid cookie, or exit to expired login
        $name = lum_getString("[SESSION_NAME]");
        if (!isset($_COOKIE[$name])) lum_redirect("/".getCurrentLanguage()."/login_expired");
        session_id($_COOKIE[$name]);
	if(!isset($_SESSION)) session_start();
        if (lum_getString("[SESSION_TIME_LIMIT_MINUTES]")>=0) setcookie($name,session_id(),time()+(60*lum_getString("[SESSION_TIME_LIMIT_MINUTES]")));
        else setcookie($name,session_id());
        return $_SESSION['application'];
    }
    
    function checkSession(){
        // check to see if the cookie and session exists
        $name = lum_getString("[SESSION_NAME]");
        if (!isset($_COOKIE[$name])) return false;
        session_id($_COOKIE[$name]);
	if(!isset($_SESSION)) return false;
	if(!isset($_SESSION['application'])) return false;
        
        return true;
    }
    
    function killSession(){
        // removes the session and cookies, effectively logs the cudtomer out
        $_SESSION['application'] = false;
        unset($_SESSION['application']);
        $name = lum_getString("[SESSION_NAME]");
        session_id($_COOKIE[$name]);
        unset($_COOKIE[$name]);
        setcookie($name,"", time()-3600);
        session_unset();
        session_destroy();
        session_write_close();
    }
    
    function str_format($str,$type){
	if (strlen($str) > 4) {
	    switch ($type){
		case 'ssn':
		    return (substr($str,0,3).'-'.substr($str,3,2).'-'.substr($str,5));
		    break;
		case 'phone':
		    return (substr($str,0,3).'-'.substr($str,3,3).'-'.substr($str,6));
		    break;
		default:
		    break;
	    }
	}
	return $str;
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
        
    function setCustomerPage($application,$page_name){
        
        if (!is_array($application)){
            $_SESSION['error_code'] = 'Application data missing.';
            lum_redirect("/".getCurrentLanguage()."/system_error");
        }
        $confirm_string = lum_getString("[ESIG_HASH_KEY]");
        $id_link = "?application_id=". urlencode(base64_encode($application['application_id']))."&login=" . md5($application['application_id'] . $confirm_string) . "&ecvt&force_new_session";

        $page_map = array(
            // Applicant
                "denied::applicant::*root" => 'loan_not_available',
                "withdrawn::applicant::*root" => 'application_withdrawn',
                "canceled::applicant::*root" => 'loan_cancelled',
            
            // Paid Customer
                "paid::customer::*root" => 'customer_complete',
                "settled::customer::*root" => 'customer_complete',
            
            // Prospect
                "disagree::prospect::*root" => 'esig_confirm_start'.$id_link,
                "confirmed::prospect::*root" => 'esig_confirm_start'.$id_link,
                "confirm_declined::prospect::*root" => 'esig_confirm_start'.$id_link,
                "pending::prospect::*root" => 'esig_confirm_start'.$id_link,
		
            // Verify
                "in_process::prospect::*root" => 'more_info',
                "addl::verification::applicant::*root" => 'more_info',
                
                "agree::prospect::*root" => 'reviewing_application',
                "dequeued::verification::applicant::*root" => 'reviewing_application',
                "queued::verification::applicant::*root" => 'reviewing_application',
            
            // Underwriting
                "queued::underwriting::applicant::*root" => 'manager_review',
                "dequeued::underwriting::applicant::*root" => 'manager_review',
		
            // Servicing (Prefunded)
                "funding_failed::servicing::customer::*root" => 'funding_failed',
                "approved::servicing::customer::*root" => 'awaiting_funding',
           
            // Servicing (Active)
                "active::servicing::customer::*root" => 'customer_portal',
                "refi::servicing::customer::*root" => 'loan_refi',
		
	    // On hold
                "hold::servicing::customer::*root" => 'on_hold',
                "hold::arrangements::collections::customer::*root" => 'on_hold',
            
            // Collection - Past Due    
                "past_due::servicing::customer::*root" => 'past_due',
                "new::collections::customer::*root" => 'past_due',
                "queued::contact::collections::customer::*root" => 'past_due',
                "dequeued::contact::collections::customer::*root" => 'past_due',
                "arrangements_failed::arrangements::collections::customer::*root" => 'past_due',

            // Collection - Deliquent
                "pending::external_collections::*root" => 'delinquent',
                "collections_rework::collections::customer::*root" => 'delinquent',
            
            // External Collections
                "sent::external_collections::*root" => 'collections',
	    
	    // Collection - Arrangements 
                "arrangements::collections::customer::*root" => 'arrangements',
                "current::arrangements::collections::customer::*root" => 'arrangements',
	    
	    // Collection - Deceased 
                "deceased::collections::customer::*root" => 'deceased',
                "unverified::deceased::collections::customer::*root" => 'deceased',
	    
	    // Collection - Bankruptcy 
                "unverified::bankruptcy::collections::customer::*root"  => 'bankruptcy',
		
	    // Account closed - bad status 
                "internal_recovered::external_collections::*root" => 'account_closed',
                "verified::bankruptcy::collections::customer::*root" => 'account_closed',
                "verified::deceased::collections::customer::*root" => 'account_closed',
                "recovered::external_collections::*root" => 'account_closed',
		
	    // Consumer counceling
                "cccs::collections::customer::*root" => 'consumer_counseling',
                "amortization::bankruptcy::collections::customer::*root" => 'consumer_counseling',
                );

        //redirect to the proper page
        if (isset($page_map[$application['status']])){
	    if (is_string($page_name)) $page_name = array($page_name);
            if (!(in_array($page_map[$application['status']],$page_name))) {
                $lookup = $page_map[$application['status']];
                lum_redirect("/".getCurrentLanguage()."/".$lookup);
            }
        } else {
            $_SESSION['error_code'] = 'Unknown page for status: '.$application['status'].' error';
            lum_redirect("/".getCurrentLanguage()."/system_error");
        }
        
        return TRUE;
    }
    function getApplicationStatus($application){
        
        if (!is_array($application)){
            $_SESSION['error_code'] = 'Application data missing.';
            lum_redirect("/".getCurrentLanguage()."/system_error");
        }
        
        $not_avail = '[cmstext Not Available status]';
        $need_esig = '[cmstext Needs Electronic Signature status]';
        $dissagree = '[cmstext Disagreed to Loan Amount status]';
        $declined = '[cmstext Customer Declined Terms status]';
        $expired = '[cmstext Loan Has Expired status]';
        $finalize = '[cmstext Finalizing Loan status]';
        $active = '[cmstext Active Loan status]';
        $on_hold = '[cmstext Loan on Hold status]';
        $fund_failed = '[cmstext Funding Failed status]';
        $refi = '[cmstext Refinancing Loan status]';
        $past_due = '[cmstext Loan Past Due status]';
        $past_due_cu = '[cmstext Loan Past Due Contact Us status]';
        $past_due_re = '[cmstext Loan Past Due Reworking Payments status]';
        $past_due_cccs = '[cmstext Loan Past Due Credit Counsuling Services status]';
        $arrange = '[cmstext Loan Past Due Arrangement status]';
        $arrange_hold = '[cmstext Loan Past Due Arrangement on Hold status]';
        $arrange_fail = '[cmstext Loan Past Due Arrangement Failed status]';
        $deceased = '[cmstext Loan Past Due Deceased status]';
        $deceased_ver = '[cmstext Loan Past Due Deceased Verified status]';
        $bankrupt = '[cmstext Loan Past Due Bankruptcy status]';
        $bankrupt_ver = '[cmstext Loan Past Due Bankruptcy Verified status]';
        $bankrupt_ammort = '[cmstext Loan Past Due Bankruptcy Ammortized status]';
        $collect = '[cmstext Going to Collections Service status]';
        $collect_sent = '[cmstext Sent to Collections Service status]';
        $collect_rec = '[cmstext Recovered by Collections Service status]';
        $collect_int = '[cmstext Recovered without Collections Service status]';
        $paid_off = '[cmstext Loan Paid Off status]';
        
        $page_map = array(
            // Applicant
                "denied::applicant::*root" => array('status' => $not_avail, 'class' => 'status_none'),
                "withdrawn::applicant::*root" => array('status' => $not_avail, 'class' => 'status_none'),
                "canceled::applicant::*root" => array('status' => $not_avail, 'class' => 'status_none'),
            
            // Prospect
                "agree::prospect::*root" => array('status' => $need_esig, 'class' => 'status_poor'),
                "disagree::prospect::*root" => array('status' => $dissagree, 'class' => 'status_poor'),
                "confirmed::prospect::*root" => array('status' => $need_esig, 'class' => 'status_poor'),
                "confirm_declined::prospect::*root" => array('status' => $declined, 'class' => 'status_poor'),
                "pending::prospect::*root" => array('status' => $need_esig, 'class' => 'status_poor'),
                "in_process::prospect::*root" => array('status' => $need_esig, 'class' => 'status_poor'),
                "preact_confirmed::prospect::*root" => array('status' => $need_esig, 'class' => 'status_poor'),
                "preact_pending::prospect::*root" => array('status' => $need_esig, 'class' => 'status_poor'),
                "expired::prospect::*root" => array('status' => $expired, 'class' => 'status_poor'),
                
            // Verify
                "verification::applicant::*root" => array('status' => $finalize, 'class' => 'status_medium'),
                "dequeued::verification::applicant::*root" => array('status' => $finalize, 'class' => 'status_medium'),
                "queued::verification::applicant::*root" => array('status' => $finalize, 'class' => 'status_medium'),
                "follow_up::verification::applicant::*root" => array('status' => $finalize, 'class' => 'status_medium'),
                "addl::verification::applicant::*root" => array('status' => $finalize, 'class' => 'status_medium'),
            
            // Underwriting
                "queued::underwriting::applicant::*root" => array('status' => $finalize, 'class' => 'status_medium'),
                "dequeued::underwriting::applicant::*root" => array('status' => $finalize, 'class' => 'status_medium'),
                "follow_up::underwriting::applicant::*root" => array('status' => $finalize, 'class' => 'status_medium'),
            
            // Servicing (Active)
                "servicing::customer::*root" => array('status' => $active, 'class' => 'status_good'),
                "active::servicing::customer::*root" => array('status' => $active, 'class' => 'status_good'),
                "approved::servicing::customer::*root" => array('status' => $active, 'class' => 'status_good'),
                "hold::servicing::customer::*root" => array('status' => $on_hold, 'class' => 'status_medium'),
                "past_due::servicing::customer::*root" => array('status' => $past_due, 'class' => 'status_bad'),
                "funding_failed::servicing::customer::*root" => array('status' => $fund_failed, 'class' => 'status_bad'),
                "refi::servicing::customer::*root" => array('status' => $refi, 'class' => 'status_good'),
                   
            // Internal Collections    
                "arrangements::collections::customer::*root" => array('status' => $arrange, 'class' => 'status_medium'),
                "bankruptcy::collections::customer::*root" => array('status' => $bankrupt, 'class' => 'status_bad'),
                "contact::collections::customer::*root" => array('status' => $past_due_cu, 'class' => 'status_bad'),
                "new::collections::customer::*root" => array('status' => $past_due, 'class' => 'status_bad'),
                "deceased::collections::customer::*root" => array('status' => $deceased , 'class' => 'status_bad'),
                "collections_rework::collections::customer::*root" => array('status' => $past_due_re, 'class' => 'status_medium'),
                "cccs::collections::customer::*root" => array('status' => $past_due_cccs, 'class' => 'status_medium'),
                "current::arrangements::collections::customer::*root" => array('status' => $arrange, 'class' => 'status_medium'),
                "arrangements_failed::arrangements::collections::customer::*root" => array('status' => $arrange_fail, 'class' => 'status_bad'),
                "hold::arrangements::collections::customer::*root" => array('status' => $arrange_hold, 'class' => 'status_bad'),
                "unverified::bankruptcy::collections::customer::*root" => array('status' => $bankrupt, 'class' => 'status_bad'),
                "verified::bankruptcy::collections::customer::*root" => array('status' => $bankrupt_ver, 'class' => 'status_medium'),
                "amortization::bankruptcy::collections::customer::*root" => array('status' => $bankrupt_ammort , 'class' => 'status_medium'),
                "dequeued::contact::collections::customer::*root" => array('status' => $past_due, 'class' => 'status_bad'),
                "follow_up::contact::collections::customer::*root" => array('status' => $past_due, 'class' => 'status_bad'),
                "queued::contact::collections::customer::*root" => array('status' => $past_due, 'class' => 'status_bad'),
                "verified::deceased::collections::customer::*root" => array('status' => $deceased_ver, 'class' => 'status_medium'),
                "unverified::deceased::collections::customer::*root" => array('status' => $deceased, 'class' => 'status_bad'),
            
           // External Collections
                "pending::external_collections::*root" => array('status' => $collect, 'class' => 'status_bad'),
                "sent::external_collections::*root" => array('status' => $collect_sent, 'class' => 'status_bad'),
                "recovered::external_collections::*root" => array('status' => $collect_rec, 'class' => 'status_medium'),
                "internal_recovered::external_collections::*root" => array('status' => $collect_int, 'class' => 'status_medium'),
            
            // Paid Customer
                "paid::customer::*root" => array('status' => $paid_off, 'class' => 'status_great'),
                "settled::customer::*root" => array('status' => $paid_off, 'class' => 'status_great'));

        return $page_map[$application['status']];
    }
?>
