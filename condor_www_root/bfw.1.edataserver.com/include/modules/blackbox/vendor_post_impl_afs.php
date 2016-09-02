<?php

/**
 * @desc A concrete implementation class to post to AFS (Axcess Financial Systems)
 */
class Vendor_Post_Impl_AFS extends Abstract_Vendor_Post_Implementation
{
	
	// must be passed as 2nd arg to Generic_Thank_You_Page:
	//		parent::Generic_Thank_You_Page($url, self::REDIRECT);
	const REDIRECT = 2;

	protected $rpc_params  = array(
			// Params which will be passed regardless of $this->mode
		'ALL'	=> array(
			'headers' => array(
				'Content-Type: text/xml; charset=utf-8',
				'SOAPAction: ""'
			),
			'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/AFS',
			'username' => 'admin',
			'password' => 'password',
		),
		// Specific cases varying with $this->mode, having higher priority than ALL.
		'LOCAL'	=> array(),
		'RC'	=> array(),
		'LIVE'	=> array(
			'username' => 'CNGPROD',
			'password' => 'password',
		),
		// The next entries are params specific to property shorts.
		// They have higher priority than all of the previous entries
		'afs_t1' => array(
			'ALL'	=> array(
				'post_url' => 'https://starstest1.checkngo.com/customerRegistration/customer',
			),
			'LOCAL'	=> array(),
			'RC'	=> array(),
			'LIVE'	=> array(
				'post_url' => 'https://starsonline.checkngo.com/customerRegistration/customer',
			),
		),
		'afs2' => array(
			'ALL'	=> array(
				'post_url' => 'https://starstest2.checkngo.com/customerRegistration/customer',
			),
			'LOCAL'	=> array(),
			'RC'	=> array(),
			'LIVE'	=> array(
				'post_url' => 'https://starsonline.checkngo.com/customerRegistration/customer',
			),
		),
		'afs3' => array(
			'ALL'	=> array(
				'post_url' => 'https://starstest3.checkngo.com/customerRegistration/customer',
			),
			'LOCAL'	=> array(),
			'RC'	=> array(),
			'LIVE'	=> array(
				'post_url' => 'https://starsonline.checkngo.com/customerRegistration/customer',
			),
		),
		'afs4' => array(
			'ALL'	=> array(
				'post_url' => 'https://starstest4.checkngo.com/customerRegistration/customer',
			),
			'LOCAL'	=> array(),
			'RC'	=> array(),
			'LIVE'	=> array(
				'post_url' => 'https://starsonline.checkngo.com/customerRegistration/customer',
			),
		),
		'afs5' => array(
			'ALL'	=> array(
				'post_url' => 'https://starstest5.checkngo.com/customerRegistration/customer',
			),
			'LOCAL'	=> array(),
			'RC'	=> array(),
			'LIVE'	=> array(
				'post_url' => 'https://starsonline.checkngo.com/customerRegistration/customer',
			),
		),
	);
	
	protected $static_thankyou = FALSE;
		
	public function Generate_Fields(&$lead_data, &$params)
	{
		$data = $lead_data['data'];
         //Paydate Freq
        if(isset($lead_data['data']['paydate_model']) &&  
           isset($lead_data['data']['paydate_model']['income_frequency']) &&
           $lead_data['data']['paydate_model']['income_frequency'] != "") 
        {   
            $freq = $lead_data['data']['paydate_model']['income_frequency'];
        }   
        elseif(isset($lead_data['data']['income_frequency']) &&  
           $lead_data['data']['income_frequency'] != "") 
        {   
            $freq = $lead_data['data']['income_frequency'];
        }   
        elseif(isset($lead_data['data']['paydate']) &&  
               isset($lead_data['data']['paydate']['frequency']) &&
               $lead_data['data']['paydate']['frequency'] != "") 
        {   
            $freq = $lead_data['data']['paydate']['frequency'];
        }   
   
     

		switch ($freq)
		{
			case 'MONTHLY':
				$income_frequency = 'MON';
				break;
			case 'WEEKLY':
				$income_frequency = 'WK';
				$day_of_week = strtoupper($data['paydate']['weekly_day']);
				break;
			case 'BI_WEEKLY':
				$income_frequency = 'BI';
				$day_of_week = strtoupper($data['paydate']['biweekly_day']);
				break;
			case 'TWICE_MONTHLY':
				$income_frequency = 'BIM';
				break;
		}
		
		
		if (!empty($day_of_week))
		{
			if ($day_of_week == 'TUE') $day_of_week = 'TUES';
			elseif ($day_of_week == 'THU') $day_of_week = 'THURS';
			
			$day_of_week = "<DAYOFWEEK>{$day_of_week}</DAYOFWEEK>";
		}

		$dob = date('Y-m-d', strtotime($data['dob']));
		$hire_date = date('Y-m-d', strtotime('-3 months'));
		$military = ($data['military'] == 'TRUE') ? 1 : 0;
		$income_type = (strcasecmp($data['income_type'], 'EMPLOYMENT') == 0) ? 'E' : 'O';
		$direct_deposit = (strcasecmp($data['income_direct_deposit'], 'TRUE') == 0) ? 'Y' : 'N';
		$first_pay_date = date('Y-m-d', strtotime($data['paydates'][0]));
		$second_pay_date = date('Y-m-d', strtotime($data['paydates'][1]));
		
		$qualify = new Qualify_2(NULL);
		$paycheck_net = $qualify->Calculate_Monthly_Net($freq, $data['income_monthly_net']);
		
		list($ref1_first, $ref1_last) = explode(' ', $data['ref_01_name_full'], 2);
		list($ref2_first, $ref2_last) = explode(' ', $data['ref_02_name_full'], 2);

		$cell_phone = '';
		if (!empty($data['phone_cell']))
		{
			$cell_phone = <<<CELL
       <PHONE TYPE="C">
          <PHONENUMBER>{$data['phone_cell']}</PHONENUMBER>
       </PHONE>
CELL;
		}

		$extension = '';
		if (!empty($data['phone_work_ext']))
		{
			$extension = "<EXTENSION>{$data['phone_work_ext']}</EXTENSION>";
		}
		
		$fields = <<<END
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xmlns:xsd="http://www.w3.org/2001/XMLSchema"
	xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
	xmlns:cus="https://customer.org/wsdl/Customer">
<soapenv:Body>
<cus:registerCustomer soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
	<userName xsi:type="xsd:string">{$params['username']}</userName>
	<password xsi:type="xsd:string">{$params['password']}</password>
<requestXml xsi:type="xsd:string">
<![CDATA[<REGISTER_AND_APPLY xmlns="http://www.qfund.net/cr">
    <VENDORKEY>SellingSource</VENDORKEY>
    <CUSTOMERINFORMATION>
       <NAME EMAIL="{$data['email_primary']}">
          <LASTNAME>{$data['name_last']}</LASTNAME>
          <FIRSTNAME>{$data['name_first']}</FIRSTNAME>
       </NAME>
       <ADDRESS>
          <LINE1>{$data['home_street']}</LINE1>
          <CITY>{$data['home_city']}</CITY>
          <STATE>{$data['home_state']}</STATE>
          <POSTALCODE>{$data['home_zip']}</POSTALCODE>
          <MONTHSATADDRESS>6</MONTHSATADDRESS>
          <OWNRESIDENCE>OTH</OWNRESIDENCE>
       </ADDRESS>
       <PHONE TYPE="H">
          <PHONENUMBER>{$data['phone_home']}</PHONENUMBER>
       </PHONE>
       <DETAILS>
          <SSN>{$data['social_security_number']}</SSN>
          <DOB>{$dob}</DOB>
          <DLTYPE>DL</DLTYPE>
          <DLSTATE>{$data['state_issued_id']}</DLSTATE>
          <DLNUMBER>{$data['state_id_number']}</DLNUMBER>
          <MARKETINGSOURCE>SLS</MARKETINGSOURCE>
          <STORENUMBER>2997</STORENUMBER>
          <IPADDRESS>{$data['client_ip_address']}</IPADDRESS>
       </DETAILS>
       <BANKINFORMATION ACCOUNTSTATUS="A">
           <ABACODE>{$data['bank_aba']}</ABACODE>
           <CHECKNO>12344343</CHECKNO><!-- Need To Be HardCoded With Any Number-->
           <ACCOUNTNO>{$data['bank_account']}</ACCOUNTNO>
           <ACCOUNTTYPE>{$data['bank_account_type']}</ACCOUNTTYPE>
       </BANKINFORMATION>
       <EMPLOYERINFORMATION NAME="{$data['employer_name']}">
          <PHONE TYPE="W">
              <PHONENUMBER>{$data['phone_work']}</PHONENUMBER>
          </PHONE>
          <MONTHSOFSERVICE>3</MONTHSOFSERVICE>
          <POSITION>Job Title</POSITION>
          <JOBSHIFT>O</JOBSHIFT>
          <HIREDATE>{$hire_date}</HIREDATE>
          <ACTIVEDUTYMILITARY>{$military}</ACTIVEDUTYMILITARY>
       </EMPLOYERINFORMATION>
       <INCOMEINFORMATION INCOMEHOLDER="S" INCOMETYPE="{$income_type}">
          <INCOME>{$paycheck_net}</INCOME>
	      <PAYFREQUENCY>{$income_frequency}</PAYFREQUENCY>
		  <NEXTPAYDATE>{$first_pay_date}</NEXTPAYDATE>
	      <SECONDPAYDATE>{$second_pay_date}</SECONDPAYDATE>
   		  <DIRECTDEPOSIT>{$direct_deposit}</DIRECTDEPOSIT>
   		  {$day_of_week}
          <GROSSPAY>{$data['income_monthly_net']}</GROSSPAY>
       </INCOMEINFORMATION>
    </CUSTOMERINFORMATION>
    <APPLICATION>
       <LOANTYPE>PDL</LOANTYPE>
    </APPLICATION>
</REGISTER_AND_APPLY>]]>
</requestXml>
</cus:registerCustomer></soapenv:Body></soapenv:Envelope>
END;
		return $fields;
	}
	
	
	public function Generate_Result(&$data_received, &$cookies)
	{
		/** Example response
			<?xml version="1.0" encoding="UTF-8"?>
		
			<SELLINGAPPROVALRESPONSE xmlns="http://www.qfund.net/cr">
				<URL>https://www.checkngo.com/externalOnlineFax.aspx?data=hd8h234843h834gh78h3o23h78h23dn923h2937hd8</URL> 
				<STATUS>
					<MESSAGE MSG_CODE=”01”>
						<DESCRIPTION></DESCRIPTION>
					</MESSAGE> 
				</STATUS> 
			</SELLINGAPPROVALRESPONSE>
		 */
	
		$data_received = html_entity_decode($data_received);
		
		$result = new Vendor_Post_Result();
		
		if (strlen(trim($data_received)) == 0)
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		elseif (preg_match('/MSG_CODE=\"([^"]+)\"/', $data_received, $m))
		{
			$code = intval($m[1]);
			preg_match('!<[^>]*DESCRIPTION>([^<]+)</[^>]*DESCRIPTION>!', $data_received, $reason); 
			
			//If the message code is less than zero, there's an error.
			if($code < 0)
			{
				$result->Set_Message('Rejected');
				$result->Set_Success(FALSE);
				$result->Set_Vendor_Decision('REJECTED');
				$result->Set_Vendor_Reason("{$code}: {$reason[1]}");
			}
			else
			{
				$result->Set_Message('Accepted');
				$result->Set_Success(TRUE);
				$result->Set_Thank_You_Content($this->Thank_You_Content($data_received));
				$result->Set_Vendor_Decision('ACCEPTED');
			}
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
		return "Vendor Post Implementation [AFS]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		preg_match('!<[^>]*URL>([^<]+)</[^>]*URL>!', $data_received, $m);
		
		return parent::Generic_Thank_You_Page($m[1], self::REDIRECT);
	}
	
}
