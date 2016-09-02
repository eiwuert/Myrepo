<?php

function client_code_displayoverview_setbuttonhtml($data)
{
	//Default States
	$data->place_in_hold_disabled = "";
    $data->cs_withdraw_disabled   = "disabled";
    $data->amortization_disabled = "";

    switch($data->status)
    {

		case "funding_failed":
			switch ($data->current_mode)
			{
				case "verification":
					/* Should Add Watch Flag be disabled here? [benb] */
					$data->follow_up_disabled = "disabled";
					break;
				case "underwriting":
					$data->follow_up_disabled = "disabled";
					break;
				case "customer_service":
					$data->follow_up_disabled   = "disabled";
					$data->withdraw_disabled    = "";
					$data->cs_withdraw_disabled = "";
					break;
				case "account_mgmt":
					/* Should Add Watch Flag be disabled here? [benb] */
					$data->withdraw_disabled    = "";
                    $data->cs_withdraw_disabled = "";
					break;
			}
			break;
		case "withdrawn":
            $data->dequeue_disabled = " disabled";
            $data->bankruptcy_notification_disabled = "disabled";
            $data->bankruptcy_verified_disabled = "disabled";
            $data->claim_app_button_disabled = "disabled";
            $data->place_in_hold_disabled = "disabled";
            $data->approve_button_disabled = "disabled";
            $data->fund_button_disabled = "disabled";
            $data->cs_reverify_disabled = "";
            $data->follow_up_disabled   = "disabled";
            break;
            
        case "denied":
            $data->dequeue_disabled = " disabled";
            $data->bankruptcy_notification_disabled = "disabled";
            $data->bankruptcy_verified_disabled = "disabled";
            $data->claim_app_button_disabled = "disabled";
            $data->place_in_hold_disabled = "disabled";
            $data->approve_button_disabled = "disabled";
            $data->fund_button_disabled = "disabled";
            $data->cs_reverify_disabled = "disabled";
            $data->follow_up_disabled   = "disabled";
            
		case "paid":
			if ($data->current_mode == "underwriting")
			{
				$data->follow_up_disabled   = "disabled";
				$data->withdraw_disabled    = "disabled";
				$data->cs_withdraw_disabled = "disabled";
				$data->deny_button_disabled = "disabled";
			}
			else if ($data->current_module != "loan_servicing")
			{
				$data->withdraw_disabled    = "";
				$data->cs_withdraw_disabled = "";
			}
			break;
        
        case "pending_transfer":
        case "follow_up":
            $data->cs_withdraw_disabled   = "disabled";
            $data->withdraw_disabled      = "disabled";
            $data->follow_up_disabled     = "disabled";
            $data->deny_button_disabled   = "disabled";
            break;
        case "active":
			$data->withdraw_disabled      = "disabled"; // mantis:2556
			$data->cs_withdraw_disabled   = "disabled";
			$data->follow_up_disabled     = "disabled";
			$data->cs_reverify_disabled   = "disabled";

			// Some sort of list of which buttons should be active where for what status would be nice [benb]
			switch ($data->current_mode)
			{
				case 'internal': // Collections
					$data->follow_up_disabled     = ""; // GF 7631
					break;		
			}
            break;
        case "dequeued":
            break;
        case "agree" :
        	/* GF #22080
        	 * Line was commented out to re-enable the Reverify button for Impact.
			 */
        				
			//$data->cs_reverify_disabled = 'disabled'; // GF #15743
            break;
        case 'current':
        	$data->cs_withdraw_disabled   = "";
        	break;
        default:
 //           $data->follow_up_disabled     = "disabled";
            break;
    }
 
    if($data->is_react == 'yes')
    {
		switch($data->level1)
		{
			case "verification":
				$data->fund_button_disabled = "disabled";
			case "underwriting":
				$data->follow_up_disabled = "disabled";
			break;
			   			
		}
    }
    else
    {
    	switch($data->level1)
		{
			case "verification":
				$data->fund_button_disabled = "disabled";
			break;
			   			
		}
    	
    }
    
	//echo "<pre>" . print_r($data,true) . "</pre>";
    if("follow_up" == $data->status)
    {
        $data->follow_up_disabled = "";
    }
    // Mantis 3803 - Limit Withdraw button to correct statuses 
    if($data->status == "approved"
    || $data->level1 == "prospect"
    || $data->level2 == "applicant")
    {
    	$data->cs_withdraw_disabled   = "";
    }
    
    // Disable ALL action buttons when app is readonly.
    if($data->isReadOnly)
    {
    	foreach($data as $data_key => $data_value)
    	{
    		if(strstr($data_key, '_disabled'))
    		{
    			$data->$data_key = 'disabled';
    		}
    	}
    }

    // If has_reacts, check to see if this is the newest application, if not, do not display a react button.
    if ($data->has_reacts)
    {
        if (Get_Parent_From_React($data->application_id) === null)
        {
            // This is the parent app, do not show reactivate button
            $data->cs_react_disabled = "disabled";
        }
    }

}

?>
