<?php

/**
 * @desc A concrete implementation class to post to LBP (LoansByPhone)
 */
class Vendor_Post_Impl_LBP extends Abstract_Vendor_Post_Implementation
{
	
	// must be passed as 2nd arg to Generic_Thank_You_Page:
	//		parent::Generic_Thank_You_Page($url, self::REDIRECT);
	const REDIRECT = 2;

/*
	public static function Get_Post_Type()
	{
		return Http_Client::HTTP_GET;
	}
*/    
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
			  	'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/LBP',
				'mode' => 0 // 0 = testing / 1 = live       
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'http://www.flowwithcash.com/Main/Forms/LeadsEngine.aspx',
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'lbp'    => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					//'post_url' => 'http://www.flowwithcash.com/Main/Forms/LeadsEngine.aspx'
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'http://www.flowwithcash.com/Main/Forms/LeadsEngine.aspx',
					'mode' => 1
					),
				),
		);
	
	protected $static_thankyou = FALSE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		$incomeGreaterThanX = ($lead_data['data']['income_monthly_net'] > 1000) ? '1' : '0';
    
        $now_date = date("m/d/Y h:i:s");
        $resType = (strtolower($lead_data['data']['residence_type']) != 'rent') ? '0' : '1';
        $address1 = $lead_data['data']['home_street'] .' '. $lead_data['data']['home_unit'];
        $direct_deposit = ($lead_data['data']['income_direct_deposit'] == 'TRUE') ? '1' : '0';
        $account_type = ($lead_data['data']['bank_account_type'] == 'TRUE') ? '1' : '0';
        $birthdate = date("m/d/Y", strtotime($lead_data['data']['dob']));
        $first_pay_date = date("m/d/Y", strtotime($lead_data['data']['paydates'][0]));
        $second_pay_date = date("m/d/Y", strtotime($lead_data['data']['paydates'][1]));
        $third_pay_date = date("m/d/Y", strtotime($lead_data['data']['paydates'][2]));
        $forth_pay_date = date("m/d/Y", strtotime($lead_data['data']['paydates'][3]));
        $length_at_job = ($lead_data['data']['employer_length'] == 'TRUE') ? 'Y' : 'N';
        $income_type = (strtolower($lead_data['data']['income_type']) == 'employment') ? '1' : '2';
        $rb_employed = (strtolower($lead_data['data']['income_type']) == 'employment') ? '1' : '0';
        $shift_hours = "8";
        $job_title = "Employee"; // now optional
        $date_hired = "09/12/2005"; // required, but generate a date more than 3 months in the past
        $date_account_created = ""; // now optional
        $how_long_at_address = "";   // now optional
        $tracker_id = 4600;
		$rbcitizen = 1; // now optional
		$phone_extension =  ($lead_data['data']['ext_work'] != '') ?  $lead_data['data']['ext_work'] :  ""; // DEBUG - will be optional
        // post on url
        // $protocal = 1;
		
		$incomeGreaterThanX = ($lead_data['data']['income_monthly_net'] > 1000) ? '1' : '0';
		
        //Paydate Freq
        if(isset($lead_data['data']['paydate_model']) && 
           isset($lead_data['data']['paydate_model']['income_frequency']) &&
           $lead_data['data']['paydate_model']['income_frequency'] != "")
        {
        	$freq = $lead_data['data']['paydate_model']['income_frequency'];
        }
        elseif(isset($lead_data['data']['income_frequency']) && 
           $lead_data['data']['income_frequency'] != "")
        {
            $freq = $lead_data['data']['income_frequency'];
        }
        elseif(isset($lead_data['data']['paydate']) && 
               isset($lead_data['data']['paydate']['frequency']) &&
               $lead_data['data']['paydate']['frequency'] != "")
        {
        	$freq = $lead_data['data']['paydate']['frequency'];
        }
        
        if(isset($freq))
        {
            //convert income frequency to requested format
            switch($freq)
            {
                case 'WEEKLY':
                    $income_frequency = '1';
                    break;
                case 'BIWEEKLY':
                case 'BI_WEEKLY':
                    $income_frequency = '2';
                    break;
                case 'TWICE_MONTHLY':
                    $income_frequency = '3';
                    break;
                case 'MONTHLY':
                    $income_frequency = '4';
                    break;
            }
            $fields["payperiod"] = $income_frequency;
        }
        
        $ref1Array = split(" ", $lead_data['data']['ref_01_name_full']);
        $ref1FirstName = $ref1Array[0];
        		$ref1LastName = "";
        for($i = 1; $i < count($ref1Array); $i++){
        	if($i > 1){
				
        		$ref1LastName .= " ";
        	}
        	$ref1LastName .= $ref1Array[$i];
        }
        $ref2Array = split(" ", $lead_data['data']['ref_02_name_full']);
        $ref2FirstName = $ref2Array[0];
        		$ref2LastName = "";
        for($i = 1; $i < count($ref2Array); $i++){
        	if($i > 1){
				
        		$ref2LastName .= " ";
        	}
        	$ref2LastName .= $ref2Array[$i];
        }
        
		// Strip out numbers
		$relationship1 = preg_replace('/[^a-zA-Z]/','',$lead_data['data']['ref_01_relationship']); 
		$relationship2 = preg_replace('/[^a-zA-Z]/','',$lead_data['data']['ref_02_relationship']);
		$ref2LastName = preg_replace('/[^a-zA-Z]/','',$ref2LastName);
		$ref1LastName = preg_replace('/[^a-zA-Z]/','',$ref1LastName);
		$ref1FirstName = preg_replace('/[^a-zA-Z]/','',$ref1FirstName);
		$ref2FirstName = preg_replace('/[^a-zA-Z]/','',$ref2FirstName);
		
        $fields = array (
            'firstname' => $lead_data['data']['name_first']
            ,'lastname' => $lead_data['data']['name_last']
            ,'email' => $lead_data['data']['email_primary']
            ,'DOB' => $lead_data['data']['dob']
            ,'SSN' => $lead_data['data']['social_security_number']
            ,'homephonenumber' => $lead_data['data']['phone_home']
            ,'cellno' => $lead_data['data']['phone_cell']
            ,'address' =>  $address1 
            ,'city' => $lead_data['data']['home_city']
            ,'state' => $lead_data['data']['home_state']
            ,'zip' => $lead_data['data']['home_zip']
            ,'howlongaddress' => $how_long_at_address      // Unknown
            ,'licensestate' => $lead_data['data']['state_issued_id']
            ,'license' => $lead_data['data']['state_id_number']
            ,'houseonrent' => $resType
            ,'rbemployed' => $rb_employed     // Unknown
            ,'rbsalary' => $incomeGreaterThanX
            ,'rbcitizen' => $rbcitizen      // 1 = US citizen / 0 = Non Citizen
            ,'dateaccountcreated' =>  $date_account_created      // Unknown
            ,'trackid' => $tracker_id      // Unknown
            ,'jobtitle' => $job_title      // Unknown
            ,'datehired' => $date_hired      // Unknown
            ,'employername' => $lead_data['data']['employer_name']
            ,'workphonenumber' => $lead_data['data']['phone_work']
            ,'phoneextension' => $phone_extension
            ,'shifthours' => $shift_hours      // Unknown
            ,'sourceofincome' =>  $rb_employed
            ,'paycycle' => $income_frequency
            ,'monthlyincome' => $lead_data['data']['income_monthly_net']
            ,'paydate1' => date("m/d/Y", strtotime($lead_data['data']['paydates'][0]))
            ,'paydate2' => date("m/d/Y", strtotime($lead_data['data']['paydates'][1]))
            ,'paydate3' => date("m/d/Y", strtotime($lead_data['data']['paydates'][2]))
            ,'deposittype' => $direct_deposit
            ,'firstname1' => $ref1FirstName
            ,'lastname1' => $ref1LastName      // Unknown
            ,'phone1' => $lead_data['data']['ref_01_phone_home']
            ,'relationship1' => $relationship1
            ,'firstname2' => $ref2FirstName
            ,'lastname2' =>  $ref2LastName      // Unknown
            ,'phone2' => $lead_data['data']['ref_02_phone_home']
            ,'relationship2' => $relationship2
            ,'bankname' => $lead_data['data']['bank_name']
            ,'accounttype' => $account_type
            ,'abarouting' => $lead_data['data']['bank_aba']
            ,'accountnumber' => $lead_data['data']['bank_account']
            ,'mode' => $params['mode']     // 0 for testing, 1 for LIVE
		);
		
		return $fields;
	}
	
	public function Generate_Result(&$data_received, &$cookies)
	{
		
		$result = new Vendor_Post_Result();
		
		if (!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		elseif (preg_match ('/status=accept/i', $data_received, $d))
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content( $data_received ) );
			$result->Set_Vendor_Decision('ACCEPTED');
		}
		else
		{
			//preg_match('/<br>Status=reject/i', $data_received, $m);
			
			$reason = preg_replace('/<br>status=reject/i','',$data_received);
			$reason = preg_replace('/status=reject/i','',$reason);
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
			$result->Set_Vendor_Reason($reason);
		}
		return($result);
	}

//	Uncomment the next line to use HTTP GET instead of POST
//	public static function Get_Post_Type() {return Http_Client::HTTP_GET;}

	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [LBP]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		
		//preg_match('/http:\/\/flowwithcash.com\/Main\/Forms\/LeadsEngineRedirect.aspx?status=accept([^<]+)/i', $data_received, $m);
		//$url = trim($m[1]);
		$url = $data_received; //preg_replace('','',$data_received); 
		
		$content = parent::Generic_Thank_You_Page($url, self::REDIRECT);
		return($content);
	}
   
}
