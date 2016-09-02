<?php

/**
 * @desc A concrete implementation class for posting to Blizzard Interactive
 * 		 the definition can be found here: http://64.4.80.27/whiteboxeyes/validate.asmx?WSDL
 *
 * @TODO  Requires DOB and Gender
 * 		  DOB needs to be formated in Datetime
 * 
 * @author TSS Developers <no-reply@SellingSource.com>
 */
class Vendor_Post_Impl_BI extends Abstract_Vendor_Post_Implementation
{
	/** 
	 * @var int
	 */
	const REDIRECT = 1;

	/**
	 * @var boolean
	 */
	protected $rpc_params  = Array
	(
		// Params which will be passed regardless of $tFalsehis->mode
		'ALL'     => Array(
			'vendor' => 'BLZ-WB-PW',
			'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/BI',
			'headers' => array(
					'Content-Type: text/xml; charset=utf-8',
				),
			),
		// Specific cases varying with $this->mode, having higher priority than ALL.
		'LOCAL'   => array(
		),
		'RC'      => array(
		),
		'LIVE'    => array(
			'post_url' => 'http://leads.blizzardi.com/whiteboxeyes/validate.asmx?WSDL',
		),
		
		// The next entries are params specific to property shorts.
		// They have higher priority than all of the previous entries
		'bi' => array(
		),
		
		'bi2' => array(
		),
						
		'bi3' => array(
			'ALL' => array(
				'vendor' => 'BLZ-D-PW',
			),
		),
	);

	/**
	 * @var boolean
	 */
	protected $static_thankyou = FALSE;	
	
	/**
	 * Generate field values for post request.
	 *
	 * @param array &$lead_data User input data.
	 * @param array &$params Values from $this->rpc_params.
	 * @return string An XML request message.
	 */
	public function Generate_Fields(&$lead_data, &$params)
	{
	
		$payperiod = array(
			'WEEKLY' => '2',
			'BI_WEEKLY' => '3',
			'TWICE_MONTHLY' => '5',
			'MONTHLY' => '4',
		);

		$ref1_array = split(" ", $lead_data['data']['ref_01_name_full']);
		$ref1_first_name = $ref1_array[0];
		$ref1_last_name = "";
		for ($i = 1; $i < count($ref1_array); $i++)
		{
			if ($i > 1)
			{
				$ref1_last_name .= " ";
			}
			$ref1_last_name .= $ref1_array[$i];
		}
		$ref2_array = split(" ", $lead_data['data']['ref_02_name_full']);
		$ref2_first_name = $ref2_array[0];
		$ref2_last_name = "";
		for ($i = 1; $i < count($ref2_array); $i++)
		{
			if ($i > 1)
			{
				$ref2_last_name .= " ";
			}
			$ref2_last_name .= $ref2_array[$i];
		}

		$frequency = '';
		if (isset($lead_data['data']['paydate']['frequency']))
		{
			$frequency = $payperiod[$lead_data['data']['paydate']['frequency']];
		}
		if (!$frequency && isset($lead_data['data']['income_frequency']))
		{
			$frequency = $payperiod[$lead_data['data']['income_frequency']];
		}
		if (!$frequency && isset($lead_data['data']['paydate_model']['income_frequency']))
		{
			$frequency = $payperiod[$lead_data['data']['paydate_model']['income_frequency']];
		}

		if (strtoupper($lead_data['data']['income_type']) == "EMPLOYMENT")
		{
			// Employment benefits
			$income_type = 4;
		}
		else if (strtoupper($lead_data['data']['income_type']) == "BENEFITS")
		{
			// Disabilit Benefits may need to change this to SSI
			$income_type = 1;
		}
		else
		{
			$income_type = 4;
		}


		$bank_account_type = (strtoupper($lead_data['data']['bank_account_type']) == "CHECKING") ? "1" : "2";
		$direct_deposit = ($lead_data['data']['income_direct_deposit']) ? 1 : 0;
		$dob = $lead_data['data']['date_dob_y'].'/'.$lead_data['data']['date_dob_m'].'/'.$lead_data['data']['date_dob_d'];



		$lastpaydate = $lead_data['data']['paydate_model']['last_pay_date'];

		if (
			!empty($lead_data['data']['name_first']) &&
			!empty($lead_data['data']['name_last']) &&
			!empty($lead_data['data']['date_dob_m']) &&
			!empty($lead_data['data']['date_dob_d']) &&
			!empty($lead_data['data']['date_dob_y']) &&
			!empty($lead_data['data']['home_street']) &&
			!empty($lead_data['data']['home_city']) &&
			!empty($lead_data['data']['home_state']) &&
			!empty($lead_data['data']['home_zip']) &&
			!empty($lead_data['data']['phone_home']) &&
			!empty($lead_data['data']['phone_work']) &&
			!empty($lead_data['data']['email_primary']) &&
			!empty($lead_data['data']['income_direct_deposit']) &&
			!empty($lead_data['data']['income_monthly_net']) &&
			!empty($lead_data['data']['bank_aba']) &&
			!empty($lead_data['data']['bank_account']) &&
			!empty($lead_data['data']['income_type']) &&
			!empty($lead_data['data']['bank_account_type']) &&
			!empty($lead_data['data']['ssn_part_1']) &&
			!empty($lead_data['data']['ssn_part_2']) &&
			!empty($lead_data['data']['ssn_part_3']) &&
			!empty($lead_data['data']['employer_name']) &&
			!empty($frequency)
			)
		{
			// No Trailing or leading SPACES!
		$promo_id = SiteConfig::getInstance()->promo_id;
		$fields =trim("<?xml version=\"1.0\" encoding=\"utf-8\"?>
<soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">
  <soap:Body>
    <acceptLead xmlns=\"http://tempuri.org/WhiteBox/Lead\">
    	<Vendor>{$params['vendor']}</Vendor>
		<VendorSubID>{$promo_id}</VendorSubID>
		<FirstName>{$lead_data['data']['name_first']}</FirstName>
		<LastName>{$lead_data['data']['name_last']}</LastName>
		<DateOfBirth>{$dob}</DateOfBirth>
		<Gender>U</Gender>
		<Address>{$lead_data['data']['home_street']}</Address>
		<City>{$lead_data['data']['home_city']}</City>
		<State>{$lead_data['data']['home_state']}</State>
		<Country>USA</Country>
		<ZipCode>{$lead_data['data']['home_zip']}</ZipCode>
		<Language>ENG</Language>
		<HomePhone>{$lead_data['data']['phone_home']}</HomePhone>
		<AltPhone>{$lead_data['data']['phone_work']}</AltPhone>
		<Email>{$lead_data['data']['email_primary']}</Email>
		<Employer>{$lead_data['data']['employer_name']}</Employer>
		<EmploymentPosition></EmploymentPosition>
		<EmployerAddress></EmployerAddress>
		<IPAddress>{$lead_data['data']['client_ip_address']}</IPAddress>
		<ReferrerURL></ReferrerURL>
		<WorkPhone>{$lead_data['data']['phone_work']}</WorkPhone>
		<WorkExtension></WorkExtension>
		<DirectDeposit>{$direct_deposit}</DirectDeposit>
		<NetIncome>{$lead_data['data']['income_monthly_net']}</NetIncome>
		<ABARouting>{$lead_data['data']['bank_aba']}</ABARouting>
		<AccountNumber>{$lead_data['data']['bank_account']}</AccountNumber>
		<JobDuration></JobDuration>
		<Source>{$income_type}</Source>
		<AccountType>{$bank_account_type}</AccountType>
		<SSN>{$lead_data['data']['ssn_part_1']}{$lead_data['data']['ssn_part_2']}{$lead_data['data']['ssn_part_3']}</SSN>
		<PayFrequency>{$frequency}</PayFrequency>
		<LastPayDate>{$lastpaydate} 12:00:00 AM</LastPayDate>
		<NextPayDate>{$lead_data['data']['paydates'][0]} 12:00:00 AM</NextPayDate>
		<DriversLicense>{$lead_data['data']['state_id_number']}</DriversLicense>
		<BankName>{$lead_data['data']['bank_name']}</BankName>
		<BankAddress></BankAddress>
		<BankPhone></BankPhone>
		<BankEmail></BankEmail>
		<Reference1FirstName>{$ref1_first_name}</Reference1FirstName>
		<Reference1LastName>{$ref1_last_name}</Reference1LastName>
		<Reference1Phone>{$lead_data['data']['ref_01_phone_home']}</Reference1Phone>
		<Reference1Email></Reference1Email>
		<Reference2FirstName>{$ref2_first_name}</Reference2FirstName>
		<Reference2LastName>{$ref2_last_name}</Reference2LastName>
		<Reference2Phone>{$lead_data['data']['ref_02_phone_home']}</Reference2Phone>
		<Reference2Email></Reference2Email>
		<SupervisorFirstName></SupervisorFirstName>
		<SupervisorLastName></SupervisorLastName>
		<SupervisorPhone></SupervisorPhone>
		<SupervisorEmail></SupervisorEmail>
		<PayrollContactFirstName></PayrollContactFirstName>
		<PayrollContactLastName></PayrollContactLastName>
		<PayrollContactPhone></PayrollContactPhone>
		<PayrollContactEmail></PayrollContactEmail>
    </acceptLead>
  </soap:Body>
</soap:Envelope>");

		}
		else
		{
			$fields = NULL;
		}
		return $fields;
	}

	/**
	 * Currently, this is the only implementation which may return 2 results back. All other implementations return
	 * one result back only.
	 * 
	 * When we post an app to lendor BI, the reponse message incidates which target the lendor sold a specific lead 
	 * to. The target specified by the lendor can be BI, or BI2. If the target is BI, we return 1 result; if the 
	 * target is BI2 (which is different from the winner we picked up), we return 2 results back and BI2 is the 
	 * winner (new_winner).
	 * 
	 * So, in this implementation, the lendor makes the final decision on who is the winner. [DY]
	 * 
	 * @param string &$data_received Data received from vendor's post_url.
	 * @param array &$cookies cookie information from the HTTP response.
	 * @return an array which contains one result's information, or an array of results.
	 */
	public function Generate_Result(&$data_received, &$cookies)
	{
		$result =  new Vendor_Post_Result();
		$result2 = new Vendor_Post_Result();

		//  Was the record accepted? [LR]
		if (preg_match("/<Purchase>True<\/Purchase>/i", strtolower(str_replace(" ", "", $data_received))) || preg_match("/^rc\./", $_SERVER['SERVER_NAME']))
		{
			$matches = array();
			preg_match('/<Tier>([\s\d]*)<\/Tier>/i', $data_received, $matches);
			$winner_tier = (!empty($matches) && !empty($matches[1])) ? intval($matches[1]) : NULL;
			
			// Who did the lender sell the record to?  (target) [LR] [DY]
			switch ($winner_tier)
			{
				case 1:
					$result->Set_Winner('BI');
					break;
				case 2:
					$result->Set_Winner('BI2');
					break;				
				case 3:
					$result->Set_Winner('BI3');
					break;
				default:
					//TODO: No proper error handler here. [DY]
					break;
			}
			
			$result->Set_Message('Accepted');
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content( $data_received ) );
			$result->Set_Vendor_Decision('ACCEPTED');

			// Did the lender sell the record to a different target from the one we chose? [LR]
			if (isset($_SESSION['blackbox']['new_winner']) && ($_SESSION['blackbox']['winner'] != $_SESSION['blackbox']['new_winner']))
			{
				// If so, we need to invalidate the initial target winner [LR]
				$result->Set_Success(FALSE);
				$result->Set_Message("Redirected");
				$result->Set_Vendor_Decision('REJECTED');
				$m = array();
				preg_match('/<RejectReason>([^<]+)<\/RejectReason>/', $data_received, $m);
				$reason = substr($m[1],0,255);
				$result->Set_Vendor_Reason($reason);

				// And create a new result set for the lender redirected target [LR]
				$result2->Set_Message("Accepted");
				$result2->Set_Winner( $_SESSION['blackbox']['new_winner']);
				$result2->Set_Success(TRUE);
				$result2->Set_Thank_You_Content( self::Thank_You_Content( $data_received ) );
				$result2->Set_Vendor_Decision('ACCEPTED');

				// We return both as an array, so we can work with both the original and redirected target. [LR]
				$res[] = $result;
				$res[] = $result2;

				return $res;
			}
		}
		elseif (!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		else
		{
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
			$m = array();
			preg_match('/<RejectReason>([^<]+)<\/RejectReason>/', $data_received, $m);
			$reason = substr($m[1],0,255);
			$result->Set_Vendor_Reason($reason);
		}

		return $result;
	}

//	Uncomment the next line to use HTTP GET instead of POST
//	public static function Get_Post_Type() {return Http_Client::HTTP_GET;}

	/**
	 * A PHP magic function.
	 *
	 * @see http://www.php.net/manual/en/language.oop5.magic.php Magic Methods
	 * @return string a string describing this class.
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [Blizzard Interactive]";
	}

	/**
	 * Generate thank you content.
	 *
	 * @param string &$data_received Input data.
	 * @return string Thank You content.
	 */
	public function Thank_You_Content(&$data_received)
	{

		$data_received = ereg_replace("[\r\t\n]","",$data_received);
		preg_match('/<Redirect>(.*)<\/Redirect>/i', $data_received, $m);
		$url = trim($m[1]);

		$content = NULL;
		$content = parent::Generic_Thank_You_Page($url);

		return($content);
	}

}
