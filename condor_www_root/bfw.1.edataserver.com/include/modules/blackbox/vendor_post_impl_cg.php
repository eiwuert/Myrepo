<?php

/**
 * @desc A concrete implementation class to post to CG (Check Giant)
 */
class Vendor_Post_Impl_CG extends Abstract_Vendor_Post_Implementation
{
	
	// must be passed as 2nd arg to Generic_Thank_You_Page:
	//		parent::Generic_Thank_You_Page($url, self::REDIRECT);
	const REDIRECT = 2;

	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
			    'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/CG',
				//'post_url' => 'https://dev2.cashnetusa.com/import-tss.html',
				'id' => '',
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'https://www.cashnetusa.com/import-tss.html',
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'cg'    => Array(
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
		$fields = array (
			'group_cd' => $params['id'],
			'lead_id' => $lead_data['application_id'],
			'first_name' => $lead_data['data']['name_first'],
			'middle_name' => $lead_data['data']['name_middle'],
			'last_name' => $lead_data['data']['name_last'],
			'home_phone_day' => $lead_data['data']['phone_home'],
			'work_phone' => $lead_data['data']['phone_work'],
			'home_phone_mobile' => $lead_data['data']['phone_cell'],
			'email' => $lead_data['data']['email_primary'],
			'dob' => $lead_data['data']['dob'],
			'ssn' => $lead_data['data']['social_security_number'],
			'driver_license' => $lead_data['data']['state_id_number'],
			'best_call_time' => $lead_data['data']['best_call_time'],
			'relative_name' => $lead_data['data']['ref_01_name_full'],
			'relative_phone_home' => $lead_data['data']['ref_01_phone_home'],
			'relative_relationship' => $lead_data['data']['ref_01_relationship'],
			'relative2_name' => $lead_data['data']['ref_02_name_full'],
			'relative2_relationship' => $lead_data['data']['ref_02_relationship'],
			'relative2_phone_home' => $lead_data['data']['ref_02_phone_home'],
			'home_address' => $lead_data['data']['home_street'] .' '. $lead_data['data']['home_unit'],
			'home_city' => $lead_data['data']['home_city'],
			'home_state' => $lead_data['data']['home_state'],
			'home_zip' => $lead_data['data']['home_zip'],
			'bank_name' => $lead_data['data']['bank_name'],
			'bank_account_number' => $lead_data['data']['bank_account'],
			'bank_routing_number' => $lead_data['data']['bank_aba'],
			'income_payment_type' => ($lead_data['data']['income_direct_deposit'] == 'TRUE') ? 'direct_deposit' : 'paper_check',
			'bank_account_type' => $lead_data['data']['bank_account_type'],
			'registration_ip' => $lead_data['data']['client_ip_address'],
			'company_name' => $lead_data['data']['employer_name'],
			'income_type' => $lead_data['data']['income_type'],
			'income_net_monthly' => $lead_data['data']['income_monthly_net'],
			'income_payment_period' => $lead_data['data']['paydate']['frequency'],
			'income_next_date1' => date("Y-m-d", strtotime($lead_data['data']['paydates'][0])),
			'income_next_date2' => date("Y-m-d", strtotime($lead_data['data']['paydates'][1])),
			'lead_password' => 'password',
			'timeout' => '13',
			'lead_source' => $_SESSION["config"]->promo_id, // added Mantis #8075 [DY]
			'country_cd' => 'US', // GForge #6759 [DY]
		);
		
		return array_map(array('Vendor_Post_Impl_CG','Clean_Value'), $fields);
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
		elseif (strpos($data_received, 'success') !== FALSE)
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
		return "Vendor Post Implementation [CG]";
	}
	
	public static function Thank_You_Content(&$data_received)
	{
		
		$url = 'https://www.cashnetusa.com/confirmation-tss/'.$_SESSION['application_id'].'.html';
		$content = parent::Generic_Thank_You_Page($url, self::REDIRECT);
		
		return($content);
		
	}
	
}
