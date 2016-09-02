<?php

/**
 * @desc A concrete implementation class for posting to Blizzard Interactive
 * 		 the definition can be found here: http://64.4.80.27/whiteboxeyes/validate.asmx?WSDL
 *
 * @TODO  Requires DOB and Gender
* 		  DOB needs to be formated in Datetime
 */
class Vendor_Post_Impl_BI_UK extends Abstract_Vendor_Post_Implementation
{
	const REDIRECT = 1;

	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $tFalsehis->mode
			'ALL'     => Array(
			    'post_url' => 'http://216.81.11.13/whiteboxeyesPTP/validate.asmx?WSDL',
				//'post_url' => 'http://rc.bfw.1.edataserver.com/header_ok.php',
				'headers' => array(
						'Content-Type: text/xml; charset=utf-8',
					)
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(

				),
			'RC'      => Array(
				
				),
			'LIVE'    => Array(
				'post_url' => 'http://leadsuk.blizzardi.com/whiteboxeyesPTP/validate.asmx?WSDL',
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
		);

	protected $static_thankyou = FALSE;	
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		$payperiod = array(
			'BI_WEEKLY' => '1',
			'MONTHLY' => '2',
		);
		//$country = (strtolower($lead_data['data']['country']) == 'england') ? "GBR" : "EUR";
		$country = 'GBR';
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

		if(strtoupper($lead_data['data']['income_type']) == "EMPLOYMENT")
		{
			// Employment benefits
			$income_type = 1;
		}
		else if(strtoupper($lead_data['data']['income_type']) == "BENEFITS")
		{
			// Disabilit Benefits may need to change this to SSI
			$income_type = 5;
		}
		else
		{
			$income_type = 1;
		}

		$direct_deposit = ($lead_data['data']['income_direct_deposit']) ? 1 : 0;
		$dob = $lead_data['data']['date_dob_y'].'/'.$lead_data['data']['date_dob_m'].'/'.$lead_data['data']['date_dob_d'];
		
		//GForge #10359 requiring the bank account type "Chequing" 
		$accounttype=2;
		
		if(strcasecmp($lead_data['bank_account_type'],"CHECKING")==0)
		{
			$accounttype=1;
		}

		// No Trailing or leading SPACES!
		$fields =trim("<?xml version=\"1.0\" encoding=\"utf-8\"?>
<soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">
  <soap:Body>
    <acceptLead xmlns=\"http://tempuri.org/WhiteBox/Lead\">
		<Vendor>UK-BLZ-PW</Vendor>
		<FirstName>{$lead_data['data']['name_first']}</FirstName>
		<LastName>{$lead_data['data']['name_last']}</LastName>
		<DateOfBirth>{$dob}</DateOfBirth>
		<Gender>U</Gender>
		<StreetName>{$lead_data['data']['home_street']}</StreetName>
		<StreetNumber>{$lead_data['data']['home_unit']}</StreetNumber>
		<City>{$lead_data['data']['home_city']}</City>
		<ZipCode>{$lead_data['data']['home_zip']}</ZipCode>
		<County>{$lead_data['data']['county']}</County>
		<Country>$country</Country>
		<Occupancy>{$lead_data['data']['own_rent']}</Occupancy>
		<HomePhone>{$lead_data['data']['phone_home']}</HomePhone>
		<CellPhone>{$lead_date['data']['phone_mobile']}</CellPhone>
		<Email>{$lead_data['data']['email_primary']}</Email>
		<Employer>{$lead_data['data']['employer_name']}</Employer>
		<EmploymentPosition>{$lead_data['data']['work_title']}</EmploymentPosition>
		<WorkPhone>{$lead_data['data']['phone_work']}</WorkPhone>
		<WorkExtension>{$lead_data['data']['ext_work']}</WorkExtension>
		<DirectDebit>{$direct_deposit}</DirectDebit>
		<NetIncome>{$lead_data['data']['income_monthly_net']}</NetIncome>
		<PayFrequency>{$frequency}</PayFrequency>
		<IPAddress>{$lead_data['data']['client_ip_address']}</IPAddress>
		<ReferrerURL></ReferrerURL>
		<IncomeSource>{$income_type}</IncomeSource>
		<NationalID>{$lead_data['data']['nin']}</NationalID>
		<AccountType>{$accounttype}</AccountType>
    </acceptLead>
  </soap:Body>
</soap:Envelope>");
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
	 * @param string $data_received Data received from vendor's post_url.
	 * @param array $cookies cookie information from the HTTP response.
	 * @return an array which contains one result's information, or an array of results.
	 */
	public function Generate_Result(&$data_received, &$cookies)
	{
		$result =  new Vendor_Post_Result();
		//  Was the record accepted? [LR]
		if (preg_match("/<Purchase>True<\/Purchase>/i", strtolower(str_replace(" ", "", $data_received))) || preg_match("/^rc\./", $_SERVER['SERVER_NAME']))
		{

			// Who did the lender sell the record to?  (target) [LR]
				$result->Set_Message("Accepted");
				$result->Set_Success(TRUE);
				$result->Set_Thank_You_Content( self::Thank_You_Content( $data_received ) );
				$result->Set_Vendor_Decision('ACCEPTED');
				
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
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [Blizzard Interactive UK]";
	}

	public function Thank_You_Content(&$data_received)
	{

	$url = 'http://www.mypoundstillpayday.co.uk/accepted.html';

        $content = parent::Generic_Thank_You_Page($url);

        return($content);
  	}



}
