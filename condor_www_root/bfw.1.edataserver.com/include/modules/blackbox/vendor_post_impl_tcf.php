<?php

/**
 * @desc A concrete implementation class for posting to Global Rebates 
 */
class Vendor_Post_Impl_TCF extends Abstract_Vendor_Post_Implementation
{
	// must be passed as 2nd arg to Generic_Thank_You_Page:
	//		parent::Generic_Thank_You_Page($url, self::REDIRECT);
	const REDIRECT = 1;

	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
				'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/TCF',
				'headers' => array(
						'Content-Type: text/xml; charset=utf-8',
						'SOAPAction: https://www.xmllead.com/Services/LeadProcessing.asmx/ProcessLead'
					)
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'https://www.xmllead.com/Services/LeadProcessing.asmx',
				),		
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
		'tcf'    => Array(
			'ALL'      => Array(
				'UserID' => 'PARTWEEKLY',
				'Password' => 'password',
				'VendorID' => 'PWY',
				'SourceID' => 'PWY',
				),
			'LOCAL'    => Array(
				),
			'RC'       => Array(
				),
			'LIVE'     => Array(
				),
			),
		'tcf2'    => Array(
			'ALL'      => Array(
				'UserID' => 'PARTNER',
				'Password' => 'password',
				'VendorID' => 'PAR',
				'SourceID' => 'PAR',
				),
			'LOCAL'    => Array(
				
				),
			'RC'       => Array(
				),
			'LIVE'     => Array(
				),
			),
		'tcf3'    => Array( // GForge #3421 [DY]
			'ALL'      => Array(
				'UserID' => 'PARTNER',
				'Password' => 'password',
				'VendorID' => 'PAR',
				'SourceID' => 'PAR35',
				),
			'LOCAL'    => Array(
				),
			'RC'       => Array(
				),
			'LIVE'     => Array(
				),
			),
		'tcf_we'    => Array(
			'ALL'      => Array(
				'UserID' => 'PARTWEEKLY',
				'Password' => 'password',
				'VendorID' => 'PWY',
				'SourceID' => 'PWY18',
				),
			'LOCAL'    => Array(
				),
			'RC'       => Array(
				),
			'LIVE'     => Array(
				),
			),	
		'tcf_we2'    => Array(
			'ALL'      => Array(
				'UserID' => 'PARTNER',
				'Password' => 'password',
				'VendorID' => 'PAR',
				'SourceID' => 'PAR21',
				),
			'LOCAL'    => Array(
				
				),
			'RC'       => Array(
				),
			'LIVE'     => Array(
				),
			),					
		'tcf_t1'    => Array(
			'ALL'      => Array(
				'UserID' => 'PARTNER',
				'Password' => 'password',
				'VendorID' => 'PAR',
				'SourceID' => 'PAR51',
				),
			'LOCAL'    => Array(
				),
			'RC'       => Array(
				),
			'LIVE'     => Array(
				),
			),					
		'can'    => Array(			//GF 4795 [TF]
			'ALL'      => Array(
				'UserID' => 'PARTWEEKLY',
				'Password' => 'P786HYS562',
				'VendorID' => 'WEE',
				'SourceID' => 'SS23',
				'headers' => array(
						'Content-Type: text/xml; charset=utf-8',
						'SOAPAction: https://www.cashadvancenetwork.com/Services/LeadProcessing.asmx/ProcessLead'
					)
				),
			'LOCAL'    => Array(
				),
			'RC'       => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'https://www.cashadvancenetwork.com/Services/LeadProcessing.asmx',
				),	
			),					
		'can2'    => Array( 		//GF 4795 [TF]
			'ALL'      => Array(
				'UserID' => 'PARTWEEKLY',
				'Password' => 'P786HYS562',
				'VendorID' => 'WEE',
				'SourceID' => 'SS27',
				'headers' => array(
						'Content-Type: text/xml; charset=utf-8',
						'SOAPAction: https://www.cashadvancenetwork.com/Services/LeadProcessing.asmx/ProcessLead'
					)
				),
			'LOCAL'    => Array(
				
				),
			'RC'       => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'https://www.cashadvancenetwork.com/Services/LeadProcessing.asmx',
				),	
			),			
		'can3'    => Array(		//GF 4795 [TF]
			'ALL'      => Array(
				'UserID' => 'PARTWEEKLY',
				'Password' => 'P786HYS562',
				'VendorID' => 'WEE',
				'SourceID' => 'SS35',
				'headers' => array(
						'Content-Type: text/xml; charset=utf-8',
						'SOAPAction: https://www.cashadvancenetwork.com/Services/LeadProcessing.asmx/ProcessLead'
					)
				),
			'LOCAL'    => Array(
				
				),
			'RC'       => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'https://www.cashadvancenetwork.com/Services/LeadProcessing.asmx',
				),	
			),	
		'can_we'    => Array(		//GF 5879 [AuMa]
			'ALL'      => Array(
				'UserID' => 'PARTWEEKLY',
				'Password' => 'P786HYS562',
				'VendorID' => 'WEE',
				'SourceID' => 'SS18',
				'headers' => array(
						'Content-Type: text/xml; charset=utf-8',
						'SOAPAction: https://www.cashadvancenetwork.com/Services/LeadProcessing.asmx/ProcessLead'
					)
				),
			'LOCAL'    => Array(
				
				),
			'RC'       => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'https://www.cashadvancenetwork.com/Services/LeadProcessing.asmx',
				),	
			),

		'can_we2'    => Array(		//GF 5879 [AuMa]
			'ALL'      => Array(
				'UserID' => 'PARTWEEKLY',
				'Password' => 'P786HYS562',
				'VendorID' => 'WEE',
				'SourceID' => 'SS21',
				'headers' => array(
						'Content-Type: text/xml; charset=utf-8',
						'SOAPAction: https://www.cashadvancenetwork.com/Services/LeadProcessing.asmx/ProcessLead'
					)
				),
			'LOCAL'    => Array(
				
				),
			'RC'       => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'https://www.cashadvancenetwork.com/Services/LeadProcessing.asmx',
				),	
			),
		);
	
	protected $static_thankyou = FALSE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{

		$payperiod = array(
			'WEEKLY' => 'W',
			'BI_WEEKLY' => 'B',
			'TWICE_MONTHLY' => 'S',
			'MONTHLY' => 'M',
		);

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

		$direct_deposit = ($lead_data['data']['income_direct_deposit']=='TRUE') ? 'Y' : 'N';
		$bank_account_type = ($lead_data['data']['bank_account_type'] == 'CHECKING') ? 'C' : 'S';
		$driver_state = !empty($lead_data['data']['state_issued_id']) ? $lead_data['data']['state_issued_id'] : $lead_data['data']['home_state'];
		$income_source = ($lead_data['data']['income_type'] == 'BENEFITS') ? "B" :"E" ;

		$phone_home = self::Format_Phone($lead_data['data']['phone_home']);
		$phone_cell = self::Format_Phone($lead_data['data']['phone_cell']);
		$phone_work = self::Format_Phone($lead_data['data']['phone_work']);
		$ref_01_phone_home = self::Format_Phone($lead_data['data']['ref_01_phone_home']);
		$ref_02_phone_home = self::Format_Phone($lead_data['data']['ref_02_phone_home']);
				

		list($y1, $m1, $d1) = explode("-", $lead_data['data']['paydates'][0]);
		$paydate1 = date("m/d/Y", mktime("0","0","0",$m1,$d1,$y1));
		
		list($y2, $m2, $d2) = explode("-", $lead_data['data']['paydates'][1]);
		$paydate2 = date("m/d/Y", mktime("0","0","0",$m2,$d2,$y2));
		
		list($y3, $m3, $d3) = explode("-", $lead_data['data']['paydates'][2]);
		$paydate3 = date("m/d/Y", mktime("0","0","0",$m3,$d3,$y3));
		
		list($y4, $m4, $d4) = explode("-", $lead_data['data']['paydates'][3]);
		$paydate4 = date("m/d/Y", mktime("0","0","0",$m4,$d4,$y4));


		$dom = new DOMDocument('1.0','utf-8');
		$soap_envelope_element = $dom->createElement('soap:Envelope');
		$soap_envelope_element->setAttribute('xmlns:xsi',"http://www.w3.org/2001/XMLSchema-instance");
		$soap_envelope_element->setAttribute('xmlns:xsd',"http://www.w3.org/2001/XMLSchema");
		$soap_envelope_element->setAttribute('xmlns:soap',"http://schemas.xmlsoap.org/soap/envelope/");
		
		$dom->appendChild($soap_envelope_element);
		
		$soap_body_element = $dom->createElement('soap:Body');
		$soap_envelope_element->appendChild($soap_body_element);
		
		$process_lead_element = $dom->createElement('ProcessLead');
		
		//GF 4795 Client requests that their post_url be referred to internally as "chowder"
		$chowder=$params['post_url'];
		$process_lead_element->setAttribute('xmlns', $chowder);
		//$process_lead_element->setAttribute('xmlns',"https://www.xmllead.com/Services/LeadProcessing.asmx");
		$soap_body_element->appendChild($process_lead_element);


		
		$credential_element = $dom->createElement('credentialData');
		$process_lead_element->appendChild($credential_element);
		$cr_element[] = $dom->createElement('UserID', $params['UserID']);
		$cr_element[] = $dom->createElement('Password',$params['Password']);
		foreach ($cr_element as $s)
		{
			$credential_element->appendChild($s);
		}


		
		$tracking_data_element = $dom->createElement('trackingData');
		$process_lead_element->appendChild($tracking_data_element);
		
		$application_element = $dom->createElement('Application');
		$tracking_data_element->appendChild($application_element);
		
		$ap_element[] = $dom->createElement('AppID', $_SESSION['application_id']);
		$ap_element[] = $dom->createElement('VendorID', $params['VendorID']);
		$ap_element[] = $dom->createElement('ReferralSourceID',$params['SourceID']);
		$ap_element[] = $dom->createElement('IPAddress',$lead_data['data']['client_ip_address']);
		$ap_element[] = $dom->createElement('VendorSubID',SiteConfig::getInstance()->promo_id); // Adding Promo ID to vendor post per Mantis #11300 [DW]
		foreach ($ap_element as $s)
		{
			$application_element->appendChild($s);
		}
		
		$advert_element = $dom->createElement('Advert');
		$tracking_data_element->appendChild($advert_element);
		
		$av_element[] = $dom->createElement('AdvertCompanyID');
		$av_element[] = $dom->createElement('BannerID');
		$av_element[] = $dom->createElement('CampaignID');
		foreach ($av_element as $s)
		{
			$advert_element->appendChild($s);
		}


		
		$customer_data_element = $dom->createElement('customerData');
		$process_lead_element->appendChild($customer_data_element);

		$cu_address_element = $dom->createElement('Address');
		$cu_ad_element[] = $dom->createElement('Address1', $lead_data['data']['home_street']);
		$cu_ad_element[] = $dom->createElement('Address2', $lead_data['data']['home_unit']);
		$cu_ad_element[] = $dom->createElement('City', $lead_data['data']['home_city']);
		$cu_ad_element[] = $dom->createElement('State', $lead_data['data']['home_state']);
		$cu_ad_element[] = $dom->createElement('ZipCode', $lead_data['data']['home_zip']);
		$cu_ad_element[] = $dom->createElement('Phone', $phone_home);
		foreach ($cu_ad_element as $s)
		{
			$cu_address_element->appendChild($s);
		}
		
		$cu_references_element = $dom->createElement('References');
		$cu_re_element[] = $dom->createElement('RefName1', $lead_data['data']['ref_01_name_full']);
		$cu_re_element[] = $dom->createElement('RefPhone1', $ref_01_phone_home);
		$cu_re_element[] = $dom->createElement('RefRelation1', $lead_data['data']['ref_01_relationship']);
		$cu_re_element[] = $dom->createElement('RefName2', $lead_data['data']['ref_02_name_full']);
		$cu_re_element[] = $dom->createElement('RefPhone2', $ref_02_phone_home);
		$cu_re_element[] = $dom->createElement('RefRelation2', $lead_data['data']['ref_02_relationship']);
		foreach ($cu_re_element as $s)
		{
			$cu_references_element->appendChild($s);
		}
		
		$cu_element[] = $dom->createElement('FirstName',$lead_data['data']['name_first']);		
		$cu_element[] = $dom->createElement('MiddleInitial');		
		$cu_element[] = $dom->createElement('LastName', $lead_data['data']['name_last']);		
		$cu_element[] = $dom->createElement('SSN',$lead_data['data']['social_security_number']);		
		$cu_element[] = $dom->createElement('DOB',$lead_data['data']['date_dob_m'].'/'.$lead_data['data']['date_dob_d'].'/'.$lead_data['data']['date_dob_y']);		
		$cu_element[] = $dom->createElement('Sex');		
		$cu_element[] = $cu_address_element;		
		$cu_element[] = $dom->createElement('WorkPhone', $phone_work);		
		$cu_element[] = $dom->createElement('Extension');		
		$cu_element[] = $dom->createElement('CellPhone', $phone_cell);		
		$cu_element[] = $dom->createElement('EmailAddress', $lead_data['data']['email_primary']);		
		$cu_element[] = $dom->createElement('DriverNumber', $lead_data['data']['state_id_number']);		
		$cu_element[] = $dom->createElement('DriverState', $driver_state);
		$cu_element[] = $dom->createElement('IsMilitary', strtolower($lead_data['data']['military']));
		$cu_element[] = $cu_references_element;
		foreach ($cu_element as $s)
		{
			$customer_data_element->appendChild($s);
		}



		$employer_data_element = $dom->createElement('employerData');
		$process_lead_element->appendChild($employer_data_element);

		$em_address_element = $dom->createElement('Address');

		$em_ad_element[] = $dom->createElement('Address1');
		$em_ad_element[] = $dom->createElement('Address2');
		$em_ad_element[] = $dom->createElement('City');
		$em_ad_element[] = $dom->createElement('State');
		$em_ad_element[] = $dom->createElement('ZipCode');
		$em_ad_element[] = $dom->createElement('Phone', $phone_work);
		foreach ($em_ad_element as $s)
		{
			$em_address_element->appendChild($s);
		}

		$em_element[] = $dom->createElement('Name', $lead_data['data']['employer_name']);		
		$em_element[] = $em_address_element;		
		$em_element[] = $dom->createElement('Fax');		
		$em_element[] = $dom->createElement('Supervisor');		
		$em_element[] = $dom->createElement('SuperPhone');		
		$em_element[] = $dom->createElement('EmploymentStatus', $income_source);		
		$em_element[] = $dom->createElement('Position');		
		$em_element[] = $dom->createElement('HireDate');		
		$em_element[] = $dom->createElement('MonthsOfService');		
		$em_element[] = $dom->createElement('WorkHours');		
		foreach ($em_element as $s)
		{
			$employer_data_element->appendChild($s);
		}


		
		$bank_data_element = $dom->createElement('bankData');
		$process_lead_element->appendChild($bank_data_element);

		$ba_element[] = $dom->createElement('Name', $lead_data['data']['bank_name']);		
		$ba_element[] = $dom->createElement('ABA', $lead_data['data']['bank_aba']);		
		$ba_element[] = $dom->createElement('AccountNumber', $lead_data['data']['bank_account']);		
		$ba_element[] = $dom->createElement('AccountType', $bank_account_type);		
		foreach ($ba_element as $s)
		{
			$bank_data_element->appendChild($s);
		}



		$income_data_element = $dom->createElement('incomeData');
		$process_lead_element->appendChild($income_data_element);
		
		$in_element[] = $dom->createElement('IncomeSource', $income_source);		
		$in_element[] = $dom->createElement('DirectDeposit', $direct_deposit);		
		$in_element[] = $dom->createElement('PayFrequency', $frequency);		
		$in_element[] = $dom->createElement('Income', $lead_data['data']['income_monthly_net']);		
		$in_element[] = $dom->createElement('PayDate1', $paydate1);		
		$in_element[] = $dom->createElement('PayDate2', $paydate2);		
		$in_element[] = $dom->createElement('PayDate3', $paydate3);		
		$in_element[] = $dom->createElement('PayDate4', $paydate4);		
		foreach ($in_element as $s)
		{
			$income_data_element->appendChild($s);
		}
		
		
		$fields_xml = $dom->saveXML();

		return $fields_xml;
	}
	
	public function Generate_Result(&$data_received, &$cookies)
	{
		$result = new Vendor_Post_Result();

		if (!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		elseif (preg_match ('/<Decision>Accepted<\/Decision>/i', $data_received, $d))
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content( $data_received ) );
			$result->Set_Vendor_Decision('ACCEPTED');
		}
		else
		{
			preg_match('/<Detail>(.*)<\/Detail>/i', $data_received, $m);
			
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
			$result->Set_Vendor_Reason($m[1]);
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
		return "Vendor Post Implementation [TCFinancial]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		preg_match('/<DocLink>(.*)<\/DocLink>/i', $data_received, $m);		
		$url =  preg_replace('/<[ ]+([^ ][^<>]*)>/i', '<${1}>', html_entity_decode(trim($m[1]))); // task #12488 [DY]		
				
		$content = parent::Generic_Thank_You_Page($url, self::REDIRECT );
		return $content;
	}

	public static function Format_Phone($phone)
	{
		if(strlen($phone) != 10) return '';

		sscanf($phone, "%3s%3s%4s", $area, $pfx, $exc);
				
		return $area . "-" . $pfx . "-" . $exc;
	}
	
}


?>
