<?php

/**
 * @desc A concrete implementation class for posting to efm, bmg172
 */
class Vendor_Post_Impl_MTE extends Abstract_Vendor_Post_Implementation
{
	
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
				'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/MTE',
				'loanamt' => 300
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
			'mte'    => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
				'post_url' => 'http://www.quickest-cash-advance.com/sshandler.asp'
					),
				),
			'mte2'   => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
				'post_url' => 'http://www.quickest-cash-advance.com/ss2handler.asp'
					),
				),
		);
	
	protected $static_thankyou = FALSE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		$url = $params['post_url'];
		$employment = ($lead_data['data']['income_type'] == 'EMPLOYMENT') ? 'Job' : 'Benefit';
		$issued_state = ($lead_data['data']['state_issued_id']) ? $lead_data['data']['state_issued_id'] : $lead_data['data']['home_state'];
		$address = $lead_data['data']['home_street'] . " ". $lead_data['data']['home_unit'];
		$dd = (strcasecmp($lead_data['data']['income_direct_deposit'], 'TRUE') === 0) ? 'Yes' : 'No';
		$bankname = ($lead_data['data']['aba_call_result']['valid'] == 1) ? $lead_data['data']['aba_call_result']['bank_name'] : $lead_data['data']['bank_name'] ;
		
		
		foreach($lead_data['data']['paydates']as $parsed_date) {
			$year = substr($parsed_date,0,4);
			$month = substr($parsed_date,5,2);
			$day = substr($parsed_date,8,2);
			$paydates[] = $month . '/' . $day . '/' . $year;
		}
		
		$fields = array (
			'IPAddress' => $lead_data['data']['client_ip_address'],
			'LoanAmt' => $params['loanamt'],
			'FirstName' => $lead_data['data']['name_first'],
			'LastName' => $lead_data['data']['name_last'],
			'SSN1' => $lead_data['data']['ssn_part_1'],
			'SSN2' => $lead_data['data']['ssn_part_2'],
			'SSN3' => $lead_data['data']['ssn_part_3'],
			'LicenseNum' => $lead_data['data']['state_id_number'],
			'LicenseState' => $issued_state,
			'DOB' => $lead_data['data']['dob'],
			'Email' => $lead_data['data']['email_primary'],
			'Address' => $address,
			'City' => $lead_data['data']['home_city'],
			'HomeState' => $lead_data['data']['home_state'],
			'ZipCode' => $lead_data['data']['home_zip'],
			'HomePhone' => $lead_data['data']['phone_home'],
			'WorkPhone' => $lead_data['data']['phone_work'],
			'WorkPhoneExt' => $lead_data['data']['ext_work'],
			'CellPhone' => $lead_data['data']['phone_cell'],
			'Income' => $lead_data['data']['income_monthly_net'],
			'IncomeSource' => $employment,
			'Employer' => $lead_data['data']['employer_name'],
			'PayCycle' => $lead_data['data']['paydate_model']['income_frequency'],
			'NextPayDate' => $paydates[0],
			'NextPayDate2' => $paydates[1],
			'NextPayDate3' => $paydates[2],
			'NextPayDate4' => $paydates[3],
			'DirectDeposit' => $dd,
			'AccntType' => $lead_data['data']['bank_account_type'],
			'BankName' => $bankname,
			'AccountNum' => $lead_data['data']['bank_account'],
			'RoutingNum' => $lead_data['data']['bank_aba'],
			'contact1_name' => $lead_data['data']['ref_01_name_full'],
			'contact1_phone' => $lead_data['data']['ref_01_phone_home'],
			'contact1_relationship' => $lead_data['data']['ref_01_relationship'],
			'contact2_name' => $lead_data['data']['ref_02_name_full'],
			'contact2_phone' => $lead_data['data']['ref_02_phone_home'],
			'contact2_relationship' => $lead_data['data']['ref_02_relationship'],			
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
		elseif(preg_match('/ACCEPTED/is', $data_received, $m))
		{
			$re = $m[0];
			
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
		return "Vendor Post Implementation [MTE]";
	}
	
	public static function Thank_You_Content(&$data_received)
	{
		$matches = array();
		preg_match('/(http.*ACCEPTED)/i', $data_received, $matches);
		$content = parent::Generic_Thank_You_Page($matches[1], self::REDIRECT);
		return($content);
		
	}
	
}
