<?php

/**
 * @desc A concrete implementation class for posting to Global Rebates 
 */
class Vendor_Post_Impl_WP extends Abstract_Vendor_Post_Implementation
{
	// must be passed as 2nd arg to Generic_Thank_You_Page:
	//		parent::Generic_Thank_You_Page($url, self::REDIRECT);
	const REDIRECT = 8;

	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
			    'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/WP',
				//'post_url' => 'http://qa.cashsupply.com/leads.aspx',
				'store_id' => '57299350001',
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'https://www.cashsupply.com/leads.aspx',
				'store_id' => '57202490001',
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
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

		$qualify = new Qualify_2(NULL);
		$paycheck_net = round($qualify->Calculate_Monthly_Net($lead_data['data']['paydate']['frequency'], $lead_data['data']['income_monthly_net']),0);
								
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


		$income_source = $lead_data['data']['income_type'] == 'EMPLOYMENT' ? "P" :"O" ;
		$income_type = $lead_data['data']['income_direct_deposit']=='TRUE'?'D':'P';

		list($y1, $m1, $d1) = explode("-", $lead_data['data']['paydates'][0]);
		$paydate1 = date("m/d/Y", mktime("0","0","0",$m1,$d1,$y1));
		
		list($y2, $m2, $d2) = explode("-", $lead_data['data']['paydates'][1]);
		$paydate2 = date("m/d/Y", mktime("0","0","0",$m2,$d2,$y2));
		
		if ($frequency == 'S')
		{
			$semi_monthly1 = $paydate1;
			$semi_monthly2 = $paydate2;				
		}
		
		$custaccttype = $lead_data['data']['bank_account_type'] == 'CHECKING' ? "C" : "S";
		
		$issued_state = ($data['state_issued_id']) ? $data['state_issued_id'] : $data['home_state'];
		
		$emp_date = "1/1/1900";
		
		$coreg_xml = false;

		$dom = new DOMDocument('1.0','utf-8');
		$root_element = $dom->createElement('EXTPOSTTRANSACTION');
		$dom->appendChild($root_element);

		$stl_element = $dom->createElement('STLTRANSACTIONINFO');
		$root_element->appendChild($stl_element);

		$ext_element = $dom->createElement('EXTTRANSACTIONDATA');
		$root_element->appendChild($ext_element);
		
		// Populate stl_element
		$s_element[] = $dom->createElement('TRANSACTIONTYPE', '100');
		$s_element[] = $dom->createElement('USERID', '1111144');
		$s_element[] = $dom->createElement('PASSWORD', '123789');
		$s_element[] = $dom->createElement('STOREID', $params['store_id']);
		$s_element[] = $dom->createElement('STLTRANSACTIONID');
		$s_element[] = $dom->createElement('EXTTRANSACTIONID');
		$s_element[] = $dom->createElement('MESSAGENUMBER');
		$s_element[] = $dom->createElement('MESSAGEDESC');
		$s_element[] = $dom->createElement('STLTRANSACTIONDATE');
		foreach ($s_element as $s)
		{
			$stl_element->appendChild($s);
		}
		
		
		// Populate ext_element
		//Customer Element
		$customer_element = $dom->createElement('CUSTOMER');
		$ext_element->appendChild($customer_element);
		
		$c_element[] = $dom->createElement('CUSTSSN',$lead_data['data']['social_security_number']);
		$c_element[] = $dom->createElement('CUSTFNAME',$lead_data['data']['name_first']);
		$c_element[] = $dom->createElement('CUSTLNAME',$lead_data['data']['name_last']);
		$c_element[] = $dom->createElement('CUSTADD1',$lead_data['data']['home_street'] .' '. $lead_data['data']['home_unit']);
		$c_element[] = $dom->createElement('CUSTCITY',$lead_data['data']['home_city']);
		$c_element[] = $dom->createElement('CUSTSTATE',$lead_data['data']['home_state']);
		$c_element[] = $dom->createElement('CUSTZIP',$lead_data['data']['home_zip']);
		$c_element[] = $dom->createElement('CUSTHOMEPHONE',$lead_data['data']['phone_home']);
		$c_element[] = $dom->createElement('CUSTMOBILEPHONE',$lead_data['data']['phone_cell']);
		$c_element[] = $dom->createElement('CUSTWORKPHONE',$lead_data['data']['phone_work']);
		$c_element[] = $dom->createElement('CUSTWORKPHONEEXT',$lead_data['data']['ext_work']);
		$c_element[] = $dom->createElement('CUSTEMAIL',$lead_data['data']['email_primary']);
		$c_element[] = $dom->createElement('CUSTDOB',$lead_data['data']['dob']);
		$c_element[] = $dom->createElement('CUST18YRSOLD','Y');
		$c_element[] = $dom->createElement('CUSTDLSTATE',$issued_state);
		$c_element[] = $dom->createElement('CUSTDLNO',$lead_data['data']['state_id_number']);
		$c_element[] = $dom->createElement('MKTCODES','72');
		$c_element[] = $dom->createElement('PDLOANRCVDFROM','PART01');
		$c_element[] = $dom->createElement('IDVERIFIED','N');
		$c_element[] = $dom->createElement('CUSTMNAME');
		$c_element[] = $dom->createElement('CUSTADD2');
		$c_element[] = $dom->createElement('CUSTZIP4');
		$c_element[] = $dom->createElement('CUSTMSGPHONE');
		$c_element[] = $dom->createElement('CUSTFAX');
		$c_element[] = $dom->createElement('CUSTMOMMAIDNAME');
		$c_element[] = $dom->createElement('UTILBILLVERIFIED');
		$c_element[] = $dom->createElement('YRSATCURRADD');
		$c_element[] = $dom->createElement('MNTHSATCURRADD');
		$c_element[] = $dom->createElement('YRSATPREVADD');
		$c_element[] = $dom->createElement('MNTHSATPREVADD');
		$c_element[] = $dom->createElement('LANDLORDNAME');
		$c_element[] = $dom->createElement('LANDLORDPHONE');
		$c_element[] = $dom->createElement('HOMESTATUS');
		$c_element[] = $dom->createElement('DISTFRMSTORE');
		$c_element[] = $dom->createElement('CUSTEDUCATION');
		$c_element[] = $dom->createElement('GROSSINCOME');
		$c_element[] = $dom->createElement('PREVIOUSCUST');
		$c_element[] = $dom->createElement('SPOUSESSN');
		$c_element[] = $dom->createElement('SPOUSEFNAME');
		$c_element[] = $dom->createElement('SPOUSEMNAME');
		$c_element[] = $dom->createElement('SPOUSELNAME');
		$c_element[] = $dom->createElement('SPOUSEDOB');
		$c_element[] = $dom->createElement('SPOUSEPHONE');
		$c_element[] = $dom->createElement('SPOUSEEMPLOYER');
		$c_element[] = $dom->createElement('SPOUSEWORKPHONE');
		$c_element[] = $dom->createElement('SPOUSEWORKPHONEEXT');
		$c_element[] = $dom->createElement('AUTOYEAR');
		$c_element[] = $dom->createElement('AUTOMAKE');
		$c_element[] = $dom->createElement('AUTOMODEL');
		$c_element[] = $dom->createElement('AUTOCOLOR');
		$c_element[] = $dom->createElement('AUTOTAG');
		$c_element[] = $dom->createElement('AUTOVIN');
		$c_element[] = $dom->createElement('AUTOVALUE');
		$c_element[] = $dom->createElement('AUTONOTE');
		
		foreach ($c_element as $c)
		{
			$customer_element->appendChild($c);
		}
		
		//Reference1
		$reference1_element = $dom->createElement('REFERENCE');
		$ext_element->appendChild($reference1_element);
		list($first,$last) = split(" ", $lead_data['data']['ref_01_name_full']);

		$r1_element[] = $dom->createElement('REFFNAME',$first);
		$r1_element[] = $dom->createElement('REFLNAME',$last);
		$r1_element[] = $dom->createElement('REFHOMEPHONE',$lead_data['data']['ref_01_phone_home']);
		$r1_element[] = $dom->createElement('REFRELATION',$lead_data['data']['ref_01_relationship']);
		$r1_element[] = $dom->createElement('REFACTIVEFLAG','P');
		$r1_element[] = $dom->createElement('REFMNAME');
		$r1_element[] = $dom->createElement('REFADD1');
		$r1_element[] = $dom->createElement('REFADD2');
		$r1_element[] = $dom->createElement('REFCITY');
		$r1_element[] = $dom->createElement('REFSTATE');
		$r1_element[] = $dom->createElement('REFZIP');
		$r1_element[] = $dom->createElement('REFZIP4');
		$r1_element[] = $dom->createElement('REFMOBILEPHONE');
		$r1_element[] = $dom->createElement('REFMSGPHONE');
		$r1_element[] = $dom->createElement('REFFAX');
		$r1_element[] = $dom->createElement('REFWORKPHONE');
		$r1_element[] = $dom->createElement('REFWORKPHONEEXT');
		$r1_element[] = $dom->createElement('REFEMAIL');
		
		foreach ($r1_element as $r1)
		{
			$reference1_element->appendChild($r1);
		}
		
		//Reference2
		$reference2_element = $dom->createElement('REFERENCE');
		$ext_element->appendChild($reference2_element);
		list($first,$last) = split(" ", $lead_data['data']['ref_02_name_full']);

		$r2_element[] = $dom->createElement('REFFNAME',$first);
		$r2_element[] = $dom->createElement('REFLNAME',$last);
		$r2_element[] = $dom->createElement('REFHOMEPHONE',$lead_data['data']['ref_02_phone_home']);
		$r2_element[] = $dom->createElement('REFRELATION',$lead_data['data']['ref_02_relationship']);
		$r2_element[] = $dom->createElement('REFACTIVEFLAG','1');
		$r2_element[] = $dom->createElement('REFMNAME');
		$r2_element[] = $dom->createElement('REFADD1');
		$r2_element[] = $dom->createElement('REFADD2');
		$r2_element[] = $dom->createElement('REFCITY');
		$r2_element[] = $dom->createElement('REFSTATE');
		$r2_element[] = $dom->createElement('REFZIP');
		$r2_element[] = $dom->createElement('REFZIP4');
		$r2_element[] = $dom->createElement('REFMOBILEPHONE');
		$r2_element[] = $dom->createElement('REFMSGPHONE');
		$r2_element[] = $dom->createElement('REFFAX');
		$r2_element[] = $dom->createElement('REFWORKPHONE');
		$r2_element[] = $dom->createElement('REFWORKPHONEEXT');
		$r2_element[] = $dom->createElement('REFEMAIL');
		
		foreach ($r2_element as $r2)
		{
			$reference2_element->appendChild($r2);
		}
		
		//Bank
		$bank_element = $dom->createElement('BANK');
		$ext_element->appendChild($bank_element);

		$b_element[] = $dom->createElement('CUSTABANO',$lead_data['data']['bank_aba']);
		$b_element[] = $dom->createElement('CUSTACCTNO',$lead_data['data']['bank_account']);
		$b_element[] = $dom->createElement('CUSTACCTTYPE',$custaccttype);
		$b_element[] = $dom->createElement('CUSTBANKNAME',$lead_data['data']['bank_name']);
		$b_element[] = $dom->createElement('BANKACTIVEFLAG','P');
		$b_element[] = $dom->createElement('CUSTBANKADD1');
		$b_element[] = $dom->createElement('CUSTBANKADD2');
		$b_element[] = $dom->createElement('CUSTBANKCITY');
		$b_element[] = $dom->createElement('CUSTBANKSTATE');
		$b_element[] = $dom->createElement('CUSTBANKZIP');
		$b_element[] = $dom->createElement('CUSTBANKZIP4');
		$b_element[] = $dom->createElement('CUSTBANKPHONE');
		$b_element[] = $dom->createElement('CUSTBANKFAX');
		$b_element[] = $dom->createElement('VOIDEDCHECKNO');
		$b_element[] = $dom->createElement('ACCTOPENDATE');
		$b_element[] = $dom->createElement('ACCTEXPDATE');
		$b_element[] = $dom->createElement('ACCT30DAYSOLD');
		$b_element[] = $dom->createElement('RECBANKSTMT');
		$b_element[] = $dom->createElement('NOOFNSF');
		$b_element[] = $dom->createElement('NOOFTRAN');
		$b_element[] = $dom->createElement('ENDINGSTMTBAL');

		foreach ($b_element as $b)
		{
			$bank_element->appendChild($b);
		}
		
		//Employer
		$employer_element = $dom->createElement('EMPLOYER');
		$ext_element->appendChild($employer_element);

		$e_element[] = $dom->createElement('TYPEOFINCOME',$income_source);
		$e_element[] = $dom->createElement('EMPNAME',$lead_data['data']['employer_name']);
		$e_element[] = $dom->createElement('AVGSALARY',$paycheck_net);
		$e_element[] = $dom->createElement('TYPEOFPAYROLL',$income_type);
		$e_element[] = $dom->createElement('PERIODICITY',$frequency);
		$e_element[] = $dom->createElement('NEXTPAYDATE',$paydate1);
		$e_element[] = $dom->createElement('SECONDPAYDATE',$paydate2);
		$e_element[] = $dom->createElement('PAYROLLACTIVEFLAG','P');
		$e_element[] = $dom->createElement('EMPADD1');
		$e_element[] = $dom->createElement('EMPADD2');
		$e_element[] = $dom->createElement('EMPCITY');
		$e_element[] = $dom->createElement('EMPSTATE');
		$e_element[] = $dom->createElement('EMPZIP');
		$e_element[] = $dom->createElement('EMPZIP4');
		$e_element[] = $dom->createElement('EMPPHONE',$lead_data['data']['phone_work']);
		$e_element[] = $dom->createElement('EMPPHONEEXT',$lead_data['data']['ext_work']);
		$e_element[] = $dom->createElement('EMPFAX');
		$e_element[] = $dom->createElement('CONTACTNAME');
		$e_element[] = $dom->createElement('CONTACTPHONE');
		$e_element[] = $dom->createElement('CONTACTPHONEEXT');
		$e_element[] = $dom->createElement('CONTACTFAX');
		$e_element[] = $dom->createElement('BENEFITSTARTDATE',$emp_date);
		$e_element[] = $dom->createElement('BENEFITENDDATE');
		$e_element[] = $dom->createElement('EMPLTYPE');
		$e_element[] = $dom->createElement('JOBTITLE');
		$e_element[] = $dom->createElement('WORKSHIFT');
		$e_element[] = $dom->createElement('INCOMEVERIFIED','Y');
		$e_element[] = $dom->createElement('PAYGARNISHMENT');
		$e_element[] = $dom->createElement('PAYBANKRUPTCY');
		$e_element[] = $dom->createElement('LASTPAYDATE');
		$e_element[] = $dom->createElement('NEXT_PAY_FREE_FORM');
		$e_element[] = $dom->createElement('SEMIMONTHLY_1ST_PAYDAY');
		$e_element[] = $dom->createElement('SEMIMONTHLY_2ND_PAYDAY');

		foreach ($e_element as $e)
		{
			$employer_element->appendChild($e);
		}
		
		//Applicationpayperiod
		$application_element = $dom->createElement('APPLICATION');
		$ext_element->appendChild($application_element);

		$a_element[] = $dom->createElement('REQUESTEDAMOUNT','300');
		$a_element[] = $dom->createElement('REQUESTEDDUEDATE',$paydate1);
		$a_element[] = $dom->createElement('REQUESTEDEFFECTIVEDATE',date("m/d/Y",strtotime("+1day")));
		$a_element[] = $dom->createElement('FINANCECHARGE','90');
		$a_element[] = $dom->createElement('APPLICATIONDATE',date("m/d/Y h:i:s A"));
		$a_element[] = $dom->createElement('LOANTYPE','S');
		$a_element[] = $dom->createElement('CLNVLOANREP');
		
		foreach ($a_element as $a)
		{
			$application_element->appendChild($a);
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
		elseif (preg_match ('/<SUCCESS>1<\/SUCCESS>/i', $data_received, $d))
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content( $data_received ) );
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
		return "Vendor Post Implementation [WebPayday]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		preg_match('/<APPLICATIONURL>(.*)<\/APPLICATIONURL>/i', $data_received, $m);
		$url = trim($m[1]);
		$content = parent::Generic_Thank_You_Page($url, self::REDIRECT);
		return($content);
	}
	
}
?>
