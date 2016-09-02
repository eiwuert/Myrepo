<?php

/**
 * @desc A concrete implementation class to post to  PD (SW Ventures, LLC)
 * 
 */
class Vendor_Post_Impl_PD extends Abstract_Vendor_Post_Implementation
{
	
	// must be passed as 2nd arg to Generic_Thank_You_Page:
	//parent::Generic_Thank_You_Page($url, self::REDIRECT);
	const REDIRECT = 6; // Seconds Before Redirect

	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
			    'post_url' => 'https://www.pdlloans.com/lead_receive.php',
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'pd1'    => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
				),
		);
	
	protected $static_thankyou = TRUE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		        //Paydate Freq
		if(	isset($lead_data['data']['paydate_model']) && 
			isset($lead_data['data']['paydate_model']['income_frequency']) &&
			$lead_data['data']['paydate_model']['income_frequency'] != "")
		{
			$freq = $lead_data['data']['paydate_model']['income_frequency'];
		}
		elseif(	isset($lead_data['data']['income_frequency']) && 
			$lead_data['data']['income_frequency'] != "")
		{
			$freq = $lead_data['data']['income_frequency'];
		}
		elseif(	isset($lead_data['data']['paydate']) && 
			isset($lead_data['data']['paydate']['frequency']) &&
			$lead_data['data']['paydate']['frequency'] != "")
		{	
			$freq = $lead_data['data']['paydate']['frequency'];
		}
		
		
		switch ($freq)
		{
			case "WEEKLY":
				$freq = "Weekly";
				break;
			case "BI_WEEKLY":
				$freq = "Bi-Weekly";
				break;		
			case "TWICE_MONTHLY":
				$freq = "Semi-Monthly";
				break;
			case "MONTHLY":
				$freq = "Monthly";
				break;
		}
		
		
		
		$fields = array (
			'FName' => $lead_data['data']['name_first'], // Required
			'LName' => $lead_data['data']['name_last'], // Required
			'Email' => $lead_data['data']['email_primary'], // Required	
			'HomePhone' => $lead_data['data']['phone_home'], // Required
			'Addr' => $lead_data['data']['home_street'] .' '. $lead_data['data']['home_unit'], // Required	
			'City' => $lead_data['data']['home_city'], // Required
			'State' => $lead_data['data']['home_state'], // Required
			'ZIP' => $lead_data['data']['home_zip'], // Required
			'SSN' => $lead_data['data']['social_security_number'], // Required
			'DOB' => $lead_data['data']['dob'], // Required
			'License' => $lead_data['data']['state_id_number'], //Required			
			'LicenseState' => (strlen($lead_data['data']['state_issued_id']) >= 2) ? $lead_data['data']['state_issued_id'] : $lead_data['data']['home_state'], //Required	
			'Employer' => $lead_data['data']['employer_name'], // equired
			'WorkPhone' => $lead_data['data']['phone_work'], //Required
			'CellPhone' => $lead_data['data']['phone_cell'],
			'MonthlyIncome' => $lead_data['data']['income_monthly_net'], //Required					
			'PayFrequency' => $freq, //Required
			'DirectDeposit' =>  ($lead_data['data']['income_direct_deposit'] == 'TRUE') ? 'Yes' : 'No', //Required
			'PayDate1' => date("m/d/Y", strtotime($lead_data['data']['paydates'][0])), //Required
			'PayDate2' => date("m/d/Y", strtotime($lead_data['data']['paydates'][1])), //Required			
			'BankName' => $lead_data['data']['bank_name'], //Required
			'RoutingNumber' => $lead_data['data']['bank_aba'], //Required			
			'BankAccountNumber' => $lead_data['data']['bank_account'], //Required			
			'BankAccountType' => $lead_data['data']['bank_account_type'], //Required		
			'Ref1' => $lead_data['data']['ref_01_name_full'], //Required			
			'Phone1' => $lead_data['data']['ref_01_phone_home'], //Required			
			'ipaddress' => $lead_data['data']['client_ip_address'], //Required
			'SrcID' => '19', // Supplied by PDL
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
		else 
		{
			$return_array = explode('|',$data_received);
			
			if($return_array[0] == 'Application Accepted')
			{
				$result->Set_Message("Accepted");
				$result->Set_Success(TRUE);
				$result->Set_Vendor_Decision('ACCEPTED');	
				$result->Set_Thank_You_Content( $this->Thank_You_Content($return_array[1]) );				
			}
			else
			{
				$result->Set_Message("Rejected");
				$result->Set_Success(FALSE);
				$result->Set_Vendor_Decision('REJECTED');	
			}
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
		return "Vendor Post Implementation [PD]";
	}
	
	public static function Thank_You_Content(&$data_received)
	{

		$return_array = explode('|',$data_received);
		$content = parent::Generic_Thank_You_Page($data_received, self::REDIRECT);
		return($content);
		
	}
	
}
