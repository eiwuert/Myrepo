<?php

/**
 * @desc A concrete implementation class for posting to some Vendor Promotions targets (vp, vp*)
 */
class Vendor_Post_Impl_VP extends Abstract_Vendor_Post_Implementation
{
	
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
			  	'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/VP',

				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'http://quickpayday.monetizeit.net/begin.do',
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'vp'    => Array(
				'ALL'      => Array(
					'p' => 'vp1ss8',
					'disable_send_loop' => TRUE,
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
				),
			'vp1_5'    => Array(
				'ALL'      => Array(
					'p' => 'vp1.5ss8',
					'disable_send_loop' => TRUE,
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
				),
			'vp2_t4'    => Array(
				'ALL'      => Array(
					'p' => 'vp2ss33',
					'disable_send_loop' => TRUE,
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
				),
			'vp3'    => Array(
				'ALL'      => Array(
					'p' => 'vp3ss17',
					'disable_send_loop' => TRUE,
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
				),
			'vp12'    => Array(
				'ALL'      => Array(
					'p' => 'vp12ss36',
					'disable_send_loop' => TRUE,
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'http://quickpayday.monetizeit.net/begin.do?view=signup&transition=finalize',
					),
				),
			'vp14'    => Array(
				'ALL'      => Array(
					'p' => 'vp14ss17',
					'disable_send_loop' => TRUE,
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'http://quickpayday.monetizeit.net/begin.do?view=signup&transition=finalize',
					),
				),
			
		);
	
	protected $static_thankyou = FALSE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		$empPayInterval = str_replace ('_', '-', strtolower ($lead_data['data']['paydate']['frequency']));
		$empPayInterval = $empPayInterval == 'twice-monthly' ? 'bi-weekly' : $empPayInterval;

		// change variables to the format vp expects
		$incomeDirectDeposit = ($lead_data['data']['income_direct_deposit'] == 'TRUE') ? 'TRUE' : 'FALSE';
		$bankAccountType = (strtoupper($lead_data['data']['bank_account_type']) == "CHECKING") ? Checking : Savings;
		$incomeType = (strtoupper($lead_data['data']['income_type']) == "EMPLOYMENT") ? 5 : 4;
		$empPayInterval = (strtoupper($lead_data['data']['paydate']['frequency']) == "WEEKLY") ? 4 : 2;
		if($empPayInterval <> 4)
		{
			$empPayInterval = (strtoupper($lead_data['data']['paydate']['frequency']) == "MONTHLY") ? 1 : 2;
		}
		$perPaycheck = $lead_data['data']['income_monthly_net']/$empPayInterval;

		$state_id = (strlen($lead_data['data']['state_id_number'] == 0)) ? "NONE" : $lead_data['data']['state_id_number'];

		$issued_state = ($lead_data['data']['state_issued_id']) ? $lead_data['data']['state_issued_id'] : $lead_data['data']['home_state'];
		
		$fields = array (
				'view' => 'signup',
				'transition' => 'finalize',
				'k' => 'ahs78t06e5',
				'p' => $params['p'],
				'ip' => $lead_data['data']['client_ip_address'],
				'customer.firstName' => $lead_data['data']['name_first'],
				'customer.lastName' => $lead_data['data']['name_last'],
				'customer.ssn' => $lead_data['data']['social_security_number'],
				'customer.dob'	=>  $lead_data['data']['date_dob_m'].'/'.$lead_data['data']['date_dob_d'].'/'.$lead_data['data']['date_dob_y'],
				'customer.address1' => $lead_data['data']['home_street'],
				//'customer.Address2' => $lead_data['data']['home_unit'],
				'customer.city'	=> $lead_data['data']['home_city'],
				'customer.state' => $lead_data['data']['home_state'],
				'customer.zip' => $lead_data['data']['home_zip'],
				'customer.email' => $lead_data['data']['email_primary'],
				'customer.phone1' => $lead_data['data']['phone_home'],
				'customer.phone2' => $lead_data['data']['phone_work'],
				'customer.driversLicense.number' => $state_id,
				'customer.driversLicense.state' => $issued_state,
				'customer.bankAccount.directDeposit' => $incomeDirectDeposit,
				'customer.bankAccount.accountType' => $bankAccountType,
				'customer.bankAccount.routingNumber' => $lead_data['data']['bank_aba'],
				'customer.bankAccount.accountNumber' => $lead_data['data']['bank_account'],
				'payDayLoan.jobTime' => '3',
				'payDayLoan.incomeSource' => $incomeType,
				'payDayLoan.employer'  => $lead_data['data']['employer_name'],
				'payDayLoan.netSalary' => $perPaycheck,
				'payDayLoan.phone2'	=> $lead_data['data']['phone_work'],
				'payDayLoan.howOftenPaid' => $empPayInterval,
				'payDayLoan.payDate1' => date("m/d/Y", strtotime($lead_data['data']['paydates'][1])),
				'payDayLoan.ref1Name' => $lead_data['data']['ref_01_name_full'],
				'payDayLoan.ref1Phone' => $lead_data['data']['ref_01_phone_home'],
				'payDayLoan.ref1Relationship' => $lead_data['data']['ref_01_relationship'],
				'payDayLoan.ref2Name' => $lead_data['data']['ref_02_name_full'],
				'payDayLoan.ref2Phone' => $lead_data['data']['ref_02_phone_home'],
				'payDayLoan.ref2Relationship' => $lead_data['data']['ref_02_relationship'],
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
			return $result;
		}
		
		preg_match ('/<responseCode>(.*)<\/responseCode>/i', $data_received, $d);

		if ($d[1] == 'OK')
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

//	Uncomment the next line to use HTTP GET instead of POST
//	public static function Get_Post_Type() {return Http_Client::HTTP_GET;}

	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [VP]";
	}
	
	public static function Thank_You_Content(&$data_received)
	{
		
		$content = NULL;
		
		if (preg_match('/<redirectURL>(.*)<\/redirectURL>/i', $data_received, $m));
		{
			
			$url = explode(' ', $m[1]);
			$url = $url[0];
			
			$content = parent::Generic_Thank_You_Page($url);
			
		}
		
		if (!$content) $content = FALSE;
		return($content);
		
	}
	
}
