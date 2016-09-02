<?php

/**
 * @desc A concrete implementation class for posting to AME
 */
class Vendor_Post_Impl_AME extends Abstract_Vendor_Post_Implementation
{
	
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
				'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/AMEDD'
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'https://www.loanwebforms.com/sellingsourceleads.aspx'
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'ame'    => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
				),
			'amedd'    => Array(
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
	
	protected $static_thankyou = FALSE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		
		$fields = array(
			'firstName' => $lead_data['data']['name_first'],
			'lastName' => $lead_data['data']['name_last'],
			'email' => $lead_data['data']['email_primary'],
			'socialSecurity' => $lead_data['data']['social_security_number'],
			'birthDate' => $lead_data['data']['dob'],
			'address' => $lead_data['data']['home_street'] .' '. $lead_data['data']['home_unit'],
			'city' => $lead_data['data']['home_city'],
			'state' => $lead_data['data']['home_state'],
			'zip' => $lead_data['data']['home_zip'],
			'homePhone' => $lead_data['data']['phone_home'],
			'employerName' => $lead_data['data']['employer_name'],
			'workPhone' => $lead_data['data']['phone_work'],
			'nextPayDay' => date("m/d/Y", strtotime($lead_data['data']['paydates'][0])),
			'secondPayDate' => date("m/d/Y", strtotime($lead_data['data']['paydates'][1])),
			'income' => $lead_data['data']['income_monthly_net'],
			'howOftenPaid' => $lead_data['data']['income_frequency'],
			'bankName' => $lead_data['data']['bank_name'],
			'routingNumber' => $lead_data['data']['bank_aba'],
			'accountNumber' => $lead_data['data']['bank_account'],
			'bankAccountType' => $lead_data['data']['bank_account_type'],
			'contactOneName' => $lead_data['data']['ref_01_name_full'],
			'contactOnePhone' => $lead_data['data']['ref_01_phone_home'],
			'contactOneAddress' => 'N/A',
			'contactTWoName' => $lead_data['data']['ref_02_name_full'],
			'contactTwoPhone' => $lead_data['data']['ref_02_phone_home'],
			'contactTwoAddress' => 'N/A',
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
		elseif ( preg_match( '/success/', $data_received ) )
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
		}

		return $result;
	}

	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [AME]";
	}
	
	public static function Thank_You_Content(&$data_received)
	{
		//Parse cookie
		$m = array();
		preg_match('/AppID=([a-z0-9-]{36})/i', $data_received, $m);
		$r_url = "https://loanwebforms.com/displayagreement.aspx?appid=" . $m[1];
		
		return parent::Generic_Thank_You_Page($r_url);
	}
}
