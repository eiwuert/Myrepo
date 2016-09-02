<?php

/**
 * @desc A concrete implementation class for posting to MEM_UK (Monetizeit)
 */
class Vendor_Post_Impl_MEM_UK extends Abstract_Vendor_Post_Implementation
{
	
	protected $rpc_params  = Array
	(
		// Params which will be passed regardless of $this->mode
		'ALL'     => Array(
		    'post_url' => 'https://ukcash.securetransactionscorp.com/begin.do',
			'k' => 'g17sikbo0k',
			),
		// Specific cases varying with $this->mode, having higher priority than ALL.
		'LOCAL'   => Array(
			),
		'RC'      => Array(
			),
		'LIVE'    => Array(
			'k' => 'o4iankn77n',
			),
		// The next entries are params specific to property shorts.
		// They have higher priority than all of the previous entries
	);
					
	protected $static_thankyou = TRUE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		
		$elm = $lead_data['data']['employer_length_months'];
		if($elm == 26)
		{
			$employer_length = 53;
		}
		else
		{
			$employer_length = 22;
		}
		
		switch(strtoupper($lead_data['data']['income_frequency']))
		{
			case "WEEKLY":
				$frequency = 6;
				break;
			case "BI_WEEKLY":
				$frequency = 5;
				break;
			default:
				$frequency = 4;
				break;
		}		
		
		switch(strtoupper($lead_data['data']['income_type']))
		{
			case "EMPLOYMENT":
				$income_source = 4;
				break;
			case "BENEFITS":
				$income_source = 1;
				$employer_length = 0;
				break;
			default:
				$income_source = 3;
				break;
		}
		
		switch(strtoupper($lead_data['data']['title']))
		{
			case "MR.":
			case "MR":
				$gender = "Male";
				break;
			case "MRS":
			case "MRS.":
			case "MISS":
			case "MISS.":
			case "MS.":
			case "MS":
				$gender = "Female";
				break;
			default:
				$gender = "";
				break;
		}
		
		$paydate_1_array = explode("-",$lead_data['data']['paydates'][0]);
		$paydate_1 = $paydate_1_array[2].$paydate_1_array[1].$paydate_1_array[0];

		$dob_array = explode("/",$lead_data['data']['dob']);
		$dob = $dob_array[1].$dob_array[0].$dob_array[2];
		
		// set up post vars
		$fields = array (
			'transition'		=> 'finalize',
			'view'			=> 'signup',
			'k'			=> $params['k'],
			'p'			=> 'CD46',
			'customer.gender'	=> $gender,
			'customer.firstName' => $lead_data['data']['name_first'],
			'customer.lastName' => $lead_data['data']['name_last'],
			'customer.dob.date'	=> $dob,
			'customer.email'	=> $lead_data['data']['email_primary'],
			'customer.address1'	=> $lead_data['data']['home_street'],
			'customer.city'	=> $lead_data['data']['home_city'],
			'customer.state'	=> ucwords(strtolower($lead_data['data']['county'])),
			'customer.zip'		=> $lead_data['data']['home_zip'],
			'customer.phone1.digits'	=> $lead_data['data']['phone_home'],
			'customer.phone2.digits'	=> $lead_data['data']['phone_work'],
			'customer.employment.employerName'	=> $lead_data['data']['employer_name'],
			'customer.employment.incomeSource'	=> $income_source,
			'customer.employment.netSalary'		=> $lead_data['data']['income_monthly_net'],
			'customer.bankAccount.directDeposit'	=> $lead_data ['data']['income_direct_deposit'],
			'customer.employment.howOftenPaid'	=> $frequency,
			'customer.employment.payDate1'		=> $paydate_1,
			'customer.employment.jobTimeMonths'	=> $employer_length,
			'customer.miscQna.answer1'		=> $lead_data['data']['debit_card'], // added this GForge 4149 [AuMa]
			'customer.miscQna.answer6'		=> $lead_data['data']['loan_amount_desired'], // changed for task 6789 [AuMa]
			'customer.nin'		=> $lead_data['data']['nin'], // added this GForge 6011 [AuMa]
		
		);
		
		return $fields;
	}
	
	public function Generate_Result(&$data_received, &$cookies)
	{
	
		$result = new Vendor_Post_Result();
		$preg_match = array();
		preg_match("/<responseCode>(.*)<\/responseCode>/",$data_received,$preg_match);
		
		if ($preg_match[1] == "OK")
		{
			$result->Set_Message('Accepted');
			$result->Set_Success(TRUE);
			$result->Set_Vendor_Decision('ACCEPTED');
		}
		else
		{
			$result->Set_Message('Rejected');
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
		}

		return $result;
	}

	//Uncomment the next line to use HTTP GET instead of POST
	public static function Get_Post_Type() {
		return Http_Client::HTTP_GET;
	}

	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [MEM_UK]";
	}
	
	public static function Thank_You_Content(&$data_received)
	{
		preg_match('/<redirectURL>(.*)<\/redirectURL>/', $data_received, $preg_matches);
		$content = parent::Generic_Thank_You_Page($preg_matches[1], self::REDIRECT);
		return($content);
		
	}
		
}
