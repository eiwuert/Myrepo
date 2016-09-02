<?php
/**
 * @desc A concrete implementation class to post to CG (Check Giant)
 */
class Vendor_Post_Impl_CG_UK extends Abstract_Vendor_Post_Implementation
{
	
	// must be passed as 2nd arg to Generic_Thank_You_Page:
	//		parent::Generic_Thank_You_Page($url, self::REDIRECT);
	const REDIRECT = 2;
	protected $vendor_redirect_url;
	
	protected $rpc_params  = Array
	(
		// Params which will be passed regardless of $this->mode
		'ALL'     => Array(
		    	'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/CG_NEW',
		 	'password' => 'password',
			),
		// Specific cases varying with $this->mode, having higher priority than ALL.
		'LOCAL'   => Array(
			),
		'RC'      => Array(
			),
		'LIVE'    => Array(
				'post_url' => 'https://leadsdev.quickquid.co.uk/import-partnerweeklyuk.html',
			),
		// The next entries are params specific to property shorts.
		// They have higher priority than all of the previous entries
		'cg_uk' => array(
			'ALL' => array(
				'post_url' => 'https://leads-test.quickquid.co.uk/import-partnerweeklyuk.html',
				'password' => 'password',
				'group_cd' => 't1',
				),
			'LIVE'    => Array(
				'post_url' => 'https://leads.quickquid.co.uk/import-partnerweeklyuk.html',
			),				
		),			
		'cg_uk2' => array(
			'ALL' => array(
				'post_url' => 'https://leads-test.quickquid.co.uk/import-partnerweeklyuk.html',
				'password' => 'password',
				'group_cd' => 't2',
				),
			'LIVE'    => Array(
				'post_url' => 'https://leads.quickquid.co.uk/import-partnerweeklyuk.html',
			),				
		),			
	);
	
	protected $static_thankyou = TRUE;
	
	private function Generate_Frequency($lead_data)
	{  //Paydate Freq
		
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
				$freq = "weekly";
				break;
			case "BI_WEEKLY":
				$freq = "other";
				break;		
			case "TWICE_MONTHLY":
				$freq = "other";
				break;
			case "MONTHLY":
				$freq = "other";
				break;
		}
		
		return $freq;
		
	}
	private function getDebitCard($number)
	{
		// rs = return string
		$rs = ""; // initialize the value
		switch ($number)
		{
			case 1:
				$rs = 'switch_maestro';
			break;
			case 2:
				$rs = 'solo';
			break;
			case 3:
				$rs = 'visa_delta';
			break;
			case 4:
				$rs = 'visa_electron';
			break;
			case 5:
				$rs = 'none';
			break;
		}
		return $rs;
	}
	private function findLengthMonths($number)
	{
		// rs = return string
		$rs = ""; // just initilizing the value
		switch ($number)
		{
			case 1:
				$rs = 'less_than_1_month';
			break;
			case 2:
				$rs = '1_month';
			break;
			case 3:
				$rs = '2_months';
			break;
			case 4:
				$rs = '3_months';
			break;
			case 7:
				$rs = '4_to_6_months';
			break;
			case 13:
				$rs = '7_months_to_1_year';
			break;
			case 25:
				$rs = '1_year_to_2_years';
			break;
			case 26:
				$rs = 'more_than_2_years';
			break;
		}
		return $rs;
	}

	public function Generate_Fields(&$lead_data, &$params)
	{
		$dob = $lead_data['data']['date_dob_d']
			.'-'.$lead_data['data']['date_dob_m']
			.'-'.$lead_data['data']['date_dob_y'];
		
		$payment_period = $this->Generate_Frequency($lead_data);
		
		$promo_id = SiteConfig::getInstance()->promo_id;
		
		$work_time = $this->findLengthMonths($lead_data['data']['employer_length_months']);
		$home_time = $this->findLengthMonths($lead_data['data']['residence_length_months']);
		
		// cg_uk no longer wants house unit and name combined but they are the same form field
		// so to differentiate, we check if home_unit is numeric. If it is numeric, we send
		// it as home_unit. If it is non-numeric we send it as house_name
		//  gforge #9227 [BA]
		if (is_numeric($lead_data['data']['home_unit']))
		{
			$house_unit = $lead_data['data']['home_unit'];
			$house_name = '';
		}
		else
		{
			$house_unit = '';
			$house_name = $lead_data['data']['home_unit'];
		}

		$fields = array (
			'lead_password' => $params['password'],
			'lead_source' =>$promo_id,
			'id_form' =>'',
			'lead_id' =>'',
			'county' =>$lead_data['data']['county'],
			'segment1' =>'',
			'house_name' =>$house_name, // gforge #9227 [BA] - teh awesome
			'house_number' =>$house_unit, // gforge #9227 [BA] - teh awesome
			'best_call_time' =>$lead_data['data']['best_call_time'],
			'title_cd' => $lead_data['data']['title'],
			'first_name' => $lead_data['data']['name_first'],
			'middle_name' => $lead_data['data']['name_middle'],
			'last_name' => $lead_data['data']['name_last'],	
			'email' => $lead_data['data']['email_primary'],		
			'dob' => $dob,				
			'street' => $lead_data['data']['home_street'],
			'postal_town_city' => $lead_data['data']['home_city'],	
			'zip' => $lead_data['data']['home_zip'],		
			'home_time' => $home_time,
			'national_insurance_number' => $lead_data['data']['nin'],
			'home_phone_day' => $lead_data['data']['phone_home'],								
			'home_type' => $lead_data['data']['residence_type'],								
			'work_phone' => $lead_data['data']['phone_work'],			
			'income_type' => strtolower($lead_data['data']['income_type']),			
			'income_net_monthly' => $lead_data['data']['income_monthly_net'],
			'income_payment_type' => ($lead_data['data']['income_direct_deposit'] == 'TRUE') ? 'direct_deposit' : 'paper_cheque',
			'income_payment_period' => $payment_period,			
			'income_next_date1' => date("d-m-Y", strtotime($lead_data['data']['paydates'][0])),
			'income_next_date2' => date("d-m-Y", strtotime($lead_data['data']['paydates'][1])),			
			'company_name' => $lead_data['data']['employer_name'],			
			'employer_position' => $lead_data['data']['work_title'],			
			'work_time' => $work_time,				
			'employer_phone' => $lead_data['data']['supervisor_phone'], 		
			'relative_name' => $lead_data['data']['ref_01_name_full'],
			'relative_phone_home' => $lead_data['data']['ref_01_phone_home'],
			'relative_relationship' => $lead_data['data']['ref_01_relationship'],
			'relative2_name' => $lead_data['data']['ref_02_name_full'],
			'relative2_relationship' => $lead_data['data']['ref_02_relationship'],
			'relative2_phone_home' => $lead_data['data']['ref_02_phone_home'],					
			'bank_account_number' => $lead_data['data']['bank_account'],			
			'sort_code' => $lead_data['data']['bank_aba'],
			'debit_card_verification' => $this->getDebitCard($lead_data['data']['debit_card']),
			'country_cd' => "GB",	
			'registration_ip' => $lead_data['data']['client_ip_address'],			
			'group_cd' => $params['group_cd'],
		);
		
		
		
		return array_map(array('Vendor_Post_Impl_CG_UK','Clean_Value'), $fields);
	}
	
	public static function Clean_Value($value)
	{
		$value = str_replace("|","",$value);
		return   str_replace("'","",$value);
	}
	
	public function Generate_Result(&$data_received, &$cookies)
	{
		
		$result = new Vendor_Post_Result();
	
		if (!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		elseif	 (strpos($data_received, 'success') !== FALSE)
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content($data_received) );
			$result->Set_Vendor_Decision('ACCEPTED');
		}		
		else
		{
			
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
			
			$m = array();
			preg_match('/reject: (.*)/i', $data_received, $m);
			$reason = substr($m[1],0,255);
			$result->Set_Vendor_Reason($reason);
		
			/*if(trim(strtolower($reason)) == 'price reject'){
				$_SESSION['bypass_withheld_targets'] = TRUE;
			}*/
					
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
		return "Vendor Post Implementation [CG_UK]";
	}
	

	
	public function Thank_You_Content(&$data_received)
	{
		//$url = $this->vendor_redirect_url . $_SESSION['application_id'] . '.html';

		// url is after 'success: ' string - this is now updated for that
		preg_match('/success: (.+)/i', $data_received, $m);
		$url = $m[1];
		$content = parent::Generic_Thank_You_Page($url, self::REDIRECT);	
		return($content);		
		
	}

	
}
