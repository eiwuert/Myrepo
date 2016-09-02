<?php

function client_code_displayoverview_setbuttonhtml($data)
{
	//We should place all company-level default button states here
	//$data->addl_button_disabled	= "disabled";
	$data->place_in_hold_disabled = "";
	$data->renew_disabled = "disabled";
	$data->skip_trace_disabled = "";
	$data->amortization_disabled = "";
	$data->hotfile_disabled = "";
    $data->follow_up_disabled = "";
	$data->moneygram_fund_button_disabled = 'disabled';
	$data->check_fund_button_disabled = 'disabled';
	$data->recall_2nd_tier_disabled = "disabled";
	$data->react_button_disabled = "";
	$data->regenerate_schedule_disabled = 'disabled';
	$data->react_button_disabled    = "disabled";
	
	if(!empty($data->app_flags['has_fatal_ach_failure']) || (!empty($data->app_flags['cust_no_ach'])))
	{
		$data->fund_button_disabled = "disabled";
	}
	/**
	 * This is a bit of a cheesy hack to ensure that JiffyCash will
	 * never show the renewal button.
	 */
	if($data->business_rules['loan_type_model'] === 'Title')
	{
		$data->can_renew_loan = $data->schedule_status->can_renew_loan;
	}
	else
	{
		$data->can_renew_loan = FALSE;
		$data->renew_hidden = 'style="display:none;"';
	}

	switch($data->business_rules['loan_type_model'])
	{
		case "Payday" :
			$data->hotfile_disabled = "disabled";
			$data->moneygram_fund_button_disabled = "disabled";
			$data->check_fund_button_disabled = 'disabled';
			break;
		case "Title"  :
			if($data->fund_button_disabled === '')
			{
				$data->check_fund_button_disabled = '';
				if($data->is_react === 'yes')
				{
					$data->moneygram_fund_button_disabled = '';
				}
			}
		default:
			break;
	}
	
	// Used for the 2nd Tier / 3rd Party Collections Recall feature
	if($data->level1 === 'external_collections' && ($data->status === 'sent' || $data->status === 'pending'))
	{
		$data->recall_2nd_tier_disabled = "";
		$data->send_to_second_tier_disabled = "disabled";
	}

    // Disable Funding module buttons for customers with a principal balance or in a customer status
	if (($data->current_module === 'funding' && $data->schedule_status->posted_and_pending_principal > 0)
		|| ($data->level2 === 'customer' || $data->level3 === 'customer'))
	{
            $data->skip_trace_disabled = "disabled";
        	$data->cs_reverify_disabled = "disabled";
        	$data->deny_button_disabled = "disabled";
        	$data->cs_withdraw_disabled = "disabled";
        	$data->withdraw_disabled    = "disabled";
        	$data->addl_button_disabled = "disabled";
        	$data->approve_button_disabled = "disabled";
        	$data->hotfile_disabled = "disabled";
        	$data->moneygram_fund_button_disabled = "disabled";
        	$data->fund_button_disabled = "disabled";
	}
	
	if($data->current_module === 'fraud' && $data->schedule_status->num_registered_events > 0 && !($data->business_rules['loan_type_model'] === 'Title' && ($data->schedule_status->has_lien_fee || $data->schedule_status->has_transfer_fee || $data->schedule_status->has_delivery_fee)))
	{
			$data->deny_button_disabled = "disabled";
			$data->withdraw_disabled = "disabled";
			$data->release_underwriting_button_disabled = "disabled";
			$data->release_verify_button_disabled = "disabled";
		
	}
	elseif ($data->business_rules['loan_type_model'] === 'Title' && ($data->schedule_status->has_lien_fee || $data->schedule_status->has_transfer_fee || $data->schedule_status->has_delivery_fee) )
	{
			$data->release_underwriting_button_disabled = "";
			$data->release_verify_button_disabled = "";
	}

	$status_util = new Status_Utility;
	$chain = $status_util->Get_Status_Chain_By_ID($data->application_status_id);

	// The Regenerate Schedule From Collections Repair
	switch($chain)
	{
		case 'indef_dequeue::collections::customer::*root': 
		case 'new::collections::customer::*root': 
		case 'skip_trace::collections::customer::*root': 
		case 'queued::contact::collections::customer::*root': 
		case 'follow_up::contact::collections::customer::*root':
			if (	$data->schedule_status->posted_total > 0 && 
					$data->schedule_status->past_due_balance <= 0 && 
					isset($data->app_flags['had_fatal_ach_failure']) && 
					empty($data->app_flags['has_fatal_ach_failure']))
				$data->regenerate_schedule_disabled = '';
			break;	
	}
	
	switch($chain)
	{
		 case "active::servicing::customer::*root":
        	$data->renew_disabled         = ($data->can_renew_loan === TRUE) ? '' : 'disabled';
            $data->withdraw_disabled      = "disabled"; // mantis:2556
            $data->cs_withdraw_disabled   = "disabled";
            $data->withdraw_disabled 	  = "disabled";
            $data->cs_reverify_disabled   = "disabled"; // mantis:9093
            $data->hotfile_disabled = "disabled";
        	$data->moneygram_fund_button_disabled = "disabled";
        	$data->deny_button_disabled = "disabled";
        break;
		case  "addl::verification::applicant::*root":
			$data->bankruptcy_notification_disabled = "disabled";
			$data->addl_button_disabled	= "disabled";
			$data->cs_reverify_disabled   = ""; 
		break; 
        case "agree::prospect::*root" :
        	$data->addl_button_disabled	= "";
        	$data->bankruptcy_notification_disabled = "disabled";
            $data->bankruptcy_verified_disabled = "disabled";
            $data->place_in_hold_disabled = "disabled";
           	$data->amortization_disabled = "disabled";
           	$data->skip_trace_disabled = "disabled";
           	$data->moneygram_fund_button_disabled = 'disabled';
			$data->cs_reverify_disabled = 'disabled'; // GF #15743
            break;
       case "amortization::bankruptcy::collections::customer::*root" :
        	$data->cs_reverify_disabled   = "disabled"; // mantis:9093
        	$data->cs_withdraw_disabled   = "disabled";
        	$data->withdraw_disabled 	  = "disabled";
        	break;  
	   case "approved::servicing::customer::*root": //Pre-Fund
        	$data->cs_reverify_disabled   = "disabled"; // mantis:9093
            $data->bankruptcy_notification_disabled = "disabled";
            $data->bankruptcy_verified_disabled = "disabled";
            $data->place_in_hold_disabled = "disabled";
           	$data->amortization_disabled = "disabled";
           	//$data->cs_withdraw_disabled   = "disabled";
           	//$data->withdraw_disabled = "disabled";
           	$data->cs_withdraw_disabled   = "";
           	$data->withdraw_disabled = "";
           	$data->approve_button_disabled = "disabled";
           	$data->hotfile_disabled = "disabled";
        	$data->moneygram_fund_button_disabled = "disabled";
        	$data->deny_button_disabled = "disabled";
        	break;
        case "arrangments::quickcheck::collections::customer::*root":
        case "arrangments_failed::arrangements::collections::customer::*root":
        case "current::arrangements::collections::customer::*root":
        	$data->cs_reverify_disabled   = "disabled"; // mantis:9093
        	$data->cs_withdraw_disabled      = "disabled";
        	$data->withdraw_disabled = "disabled";
        	break;

        case "confirmed::prospect::*root":
			$data->bankruptcy_notification_disabled = "disabled";
			$data->bankruptcy_verified_disabled = "disabled";
			$data->place_in_hold_disabled = "disabled";
			$data->amortization_disabled = "disabled";
			break;
        			
		case 'confirmed::fraud::applicant::*root':
			$data->bankruptcy_notification_disabled = "disabled";
			$data->bankruptcy_verified_disabled = "disabled";
			$data->place_in_hold_disabled = "disabled";
			$data->amortization_disabled = "disabled";
			break;
        		
        case "confirm_declined::prospect::*root":
        case "declined::prospect::*root":
        	$data->cs_reverify_disabled   = "disabled"; // mantis:9093
        	break;

        case "denied::applicant::*root":
            $data->dequeue_disabled = "disabled";
            $data->bankruptcy_notification_disabled = "disabled";
            $data->bankruptcy_verified_disabled = "disabled";
            $data->claim_app_button_disabled = "disabled";
            $data->place_in_hold_disabled = "disabled";
            $data->approve_button_disabled = "disabled";
            $data->cs_reverify_disabled = "disabled";
            $data->deny_button_disabled = "disabled";
        	$data->cs_withdraw_disabled = "disabled";
        	$data->withdraw_disabled = "disabled";
        	$data->withdraw_disabled      = "disabled";
        	$data->fund_button_disabled = "disabled"; // mantis:9622
        	$data->amortization_disabled = "disabled";
        	$data->skip_trace_disabled = "disabled";
			break;

        case 'queued::contact::collections::customer::*root':
        case 'dequeued::contact::collections::customer::*root':
			$data->cs_reverify_disabled   = "disabled";
       		$data->cs_withdraw_disabled  = "disabled";
    		$data->withdraw_disabled = "disabled";
			break;
		case 'queued::verification::applicant::*root':
		case 'dequeued::verification::applicant::*root':	
			$data->bankruptcy_notification_disabled = "disabled";
            $data->bankruptcy_verified_disabled = "disabled";
            $data->place_in_hold_disabled = "disabled";
           	$data->amortization_disabled = "disabled";
           	$data->skip_trace_disabled = "disabled";
           	$data->addl_button_disabled = "";
           	$data->moneygram_fund_button_disabled = "disabled";
        	$data->fund_button_disabled = "disabled";
        	$data->check_fund_button_disabled = 'disabled';
        	$data->approve_button_disabled = "";
		break;
		
		case 'dequeued::high_risk::applicant::*root':
		case 'queued::high_risk::applicant::*root':
			$data->bankruptcy_notification_disabled = "disabled";
            $data->bankruptcy_verified_disabled = "disabled";
            $data->place_in_hold_disabled = "disabled";
           	$data->amortization_disabled = "disabled";
           	$data->skip_trace_disabled = "disabled";
           	$data->addl_button_disabled = "";
           	$data->approve_button_disabled = "disabled";	
           	$data->release_underwriting_button_disabled = "";
			$data->release_verify_button_disabled = "";
			$data->deny_button_disabled = "";
			$data->withdraw_disabled = "";
			
		break;
		case 'queued::underwriting::applicant::*root':
		case 'dequeued::underwriting::applicant::*root':
		case 'queued::fraud::applicant::*root':	
		
		case 'dequeued::fraud::applicant::*root':
			$data->bankruptcy_notification_disabled = "disabled";
            $data->bankruptcy_verified_disabled = "disabled";
            $data->place_in_hold_disabled = "disabled";
           	$data->amortization_disabled = "disabled";
           	$data->skip_trace_disabled = "disabled";
           	$data->addl_button_disabled = "";
           	$data->approve_button_disabled = "disabled";
           	$data->release_underwriting_button_disabled = "";
			$data->release_verify_button_disabled = "";
			$data->deny_button_disabled = "";
			$data->withdraw_disabled = "";
         // $data->moneygram_fund_button_disabled = "disabled";
        //	$data->fund_button_disabled = "disabled";
			break;

		case 'follow_up::contact::collections::customer::*root':
			$data->bankruptcy_notification_disabled = "";
			$data->bankruptcy_verified_disabled = "disabled";
			$data->place_in_hold_disabled 	= "disabled";
			$data->amortization_disabled 	= "disabled";
			$data->cs_withdraw_disabled  	= "disabled";
			$data->withdraw_disabled 		= "disabled";
			$data->cs_reverify_disabled 	= "disabled";
			break;
		
		case 'follow_up::high_risk::applicant::*root':
		case 'follow_up::fraud::applicant::*root':
			$data->bankruptcy_notification_disabled = "disabled";
			$data->bankruptcy_verified_disabled = "disabled";
			$data->place_in_hold_disabled 	= "disabled";
			$data->amortization_disabled 	= "disabled";
			$data->addl_button_disabled 	= "";
			break;
		
		case 'follow_up::underwriting::applicant::*root':
		case 'follow_up::verification::applicant::*root':
			$data->bankruptcy_notification_disabled = "disabled";
			$data->bankruptcy_verified_disabled = "disabled";
			$data->place_in_hold_disabled 	= "disabled";
			$data->amortization_disabled 	= "disabled";
			$data->addl_button_disabled		= "";
			$data->moneygram_fund_button_disabled = "disabled";
        	$data->fund_button_disabled = "disabled";
			break;

		case "hold::arrangements::collections::customer::*root":
        case "indef_dequeue::collections::customer::*root" :
        case "new::collections::customer::*root":
        	$data->cs_reverify_disabled   = "disabled"; // mantis:9093
        	$data->cs_withdraw_disabled   = "disabled";
        	$data->withdraw_disabled 	  = "disabled";
        	break;
        	
        case "funding_failed::servicing::customer::*root":
        	$data->cs_reverify_disabled   = ""; // GF: 6035
        	$data->cs_withdraw_disabled   = "disabled";
        	$data->withdraw_disabled 	  = "disabled";
        	break;

        case "hotfile::verification::applicant::*root":
        	$data->hotfile_disabled = "disabled";
        	$data->bankruptcy_notification_disabled = "disabled";
            $data->bankruptcy_verified_disabled = "disabled";
            $data->place_in_hold_disabled 		= "disabled";
           	$data->amortization_disabled 		= "disabled";
        	break;

        case "paid::customer::*root":
            $data->bankruptcy_notification_disabled = "disabled";
            $data->bankruptcy_verified_disabled = "disabled";
            $data->place_in_hold_disabled 		= "disabled";
            $data->skip_trace_disabled 			= "disabled";
        	$data->amortization_disabled 		= "disabled";
        	$data->cs_reverify_disabled 		= "disabled";
        	$data->deny_button_disabled 		= "disabled";
        	$data->cs_withdraw_disabled 		= "disabled";
        	$data->withdraw_disabled    		= "disabled";
        	$data->hotfile_disabled 			= "disabled";
        	$data->react_button_disabled    	= "";
        	break;

        case "past_due::servicing::customer::*root":
        	$data->cs_withdraw_disabled  = "disabled";
        	$data->withdraw_disabled 	 = "disabled";
        	break;
        
        case "pending::external_collections::*root":
        	$data->cs_reverify_disabled   = "disabled"; // mantis:9093
            $data->bankruptcy_notification_disabled = "disabled";
            $data->bankruptcy_verified_disabled = "disabled";
            $data->place_in_hold_disabled 		= "disabled";
           	$data->amortization_disabled 		= "disabled";
           	$data->skip_trace_disabled 			= "disabled";
        	break;
				
		case "ready::quickcheck::collections::customer::*root":
		case "return::quickcheck::collections::customer::*root":
		case "sent::quickcheck::collections::customer::*root":
        	$data->cs_reverify_disabled   = "disabled"; // mantis:9093
        	$data->cs_withdraw_disabled   = "disabled";
        	$data->withdraw_disabled 	  = "disabled";
        	break;
        	
        case "recovered::external_collections::*root":
        	$data->cs_reverify_disabled 	= "disabled"; // mantis:9093
        	$data->skip_trace_disabled 		= "disabled";
        	$data->react_button_disabled    = "disabled";
        	$data->bankruptcy_notification_disabled = "disabled";
        	break;

        case "skip_trace::collections::customer::*root":
        	$data->cs_reverify_disabled   = "disabled"; // mantis:9093
        	$data->cs_withdraw_disabled   = "disabled";
        	$data->withdraw_disabled 	  = "disabled";
        	$data->skip_trace_disabled 		= "";
        	break;

        case 'soft_fax::prospect::*root':
        	$data->cs_reverify_disabled   = "";
        	break;

        case "unverified::bankruptcy::collections::customer::*root":
        case "verified::bankruptcy::collections::customer::*root":
        	$data->cs_reverify_disabled 	= "disabled"; // mantis:9093
        	$data->cs_withdraw_disabled 	= "disabled";
        	$data->withdraw_disabled 		= "disabled";
        	$data->addl_button_disabled 	= "";
        	break;

        case "withdrawn::applicant::*root":
        	$data->withdraw_disabled 	= "disabled";
        	$data->cs_withdraw_disabled = "disabled";
        	$data->withdraw_disabled 	= "disabled";
            $data->bankruptcy_notification_disabled = "disabled";
            $data->bankruptcy_verified_disabled = "disabled";
            $data->react_button_disabled = "disabled";
            $data->fund_button_disabled = "disabled";
            $data->place_in_hold_disabled 		= "disabled";
           	$data->amortization_disabled 		= "disabled";
        	break;
        default:
            break;
    }
	
}

?>
