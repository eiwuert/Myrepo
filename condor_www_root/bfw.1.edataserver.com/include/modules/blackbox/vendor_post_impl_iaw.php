<?php
/**
 * Vendor Implementation for IAW
 * 
 * This class implements the vendor post for interADworks
 * 
 * @author Jason Gabriele <jason.gabriele@sellingsource.com>
 */
class Vendor_Post_Impl_IAW extends Abstract_Vendor_Post_Implementation
{
	// must be passed as 2nd arg to Generic_Thank_You_Page:
	//		parent::Generic_Thank_You_Page($url, self::REDIRECT);
	const REDIRECT = 8;

	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
			  'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/IAW',
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'https://www.eloanserver.com/PartnerWeeklyComplete.asp?ADID=5900'
				),
			'iaw'    => Array(
				'ALL'      => Array(
					'iaw2_m8945' => TRUE,
					'iaw_g9636' => TRUE,
					'adid'	=> '5905',
					'test'	=> TRUE,
					'post_url' => 'https://www.eloanserver.com/marketing/marketingpost.asmx',
					'headers' => array(
							'Content-Type: text/xml; charset=utf-8',
						),					
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'test'	=> FALSE,
					'post_url' => 'https://www.eloanserver.com/marketing/marketingpost.asmx?op=Post',
					),
				),
			'iaw_we2' => array(
				// Params which will be passed regardless of $this->mode
				'ALL'     => Array(
				  'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/IAW',
					),
				// Specific cases varying with $this->mode, having higher priority than ALL.
				'LOCAL'   => Array(
					),
				'RC'      => Array(
					),
				'LIVE'    => Array(
					'post_url' => 'https://www.eloanserver.com/PartnerWeeklyComplete.asp?ADID=5900'
				),
			),
//			[#10907] Updated Posting Specs - InterADworks (iaw2) [TF]
//			'iaw2'    => Array(
//				'ALL'      => Array(
//					'iaw2_m8945' => true, // for control purpose. Mantis #8945 [DY]
//					'adid'	=> '5900',
//					'test'	=> TRUE,
//					'post_url' => 'https://www.eloanserver.com/MarketerPost.asp?ADID=5900',
//					//'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/IAW2',
//					'headers' => array(
//							'Content-Type: text/xml; charset=utf-8',
//						),					
//					),
//				'LOCAL'    => Array(
//					),
//				'RC'       => Array(
//					),
//				'LIVE'     => Array(
//					'test'	=> FALSE,
//					//GF [#10907] Updated Posting Specs - InterADworks (iaw2)[TF]
//					'post_url' => 'https://www.eloanserver.com/MarketerPost.asp?ADID=5900'
//					
//					//'post_url' => 'https://www.eloanserver.com/purposecashadvance/marketing/marketingpost.asmx'
//					),
//				),
			'iaw_we'    => Array(
				'ALL'      => Array(
					'iaw2_m8945' => true,
					'adid'	=> '5904',
					'test'	=> TRUE,
					'post_url' => 'http://blackbox.post.server.ds95.tss:80/p.php/IAW2',
					'headers' => array(
							'Content-Type: text/xml; charset=utf-8',
						),					
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'test'	=> FALSE,
					'post_url' => 'https://www.eloanserver.com/purposecashadvance/marketing/marketingpost.asmx'
					),
				),
			'iaw3'    => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.eloanserver.com/repcomplete.asp?adid=5903'
					),
				),
			'iaw4'    => Array(
				'ALL'      => Array(
					'iaw2_m8945' => true, // for control purpose. Mantis #8945 [DY]
					'adid'	=> '5906',
					'test'	=> TRUE,
					'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/IAW2',
					'headers' => array(
							'Content-Type: text/xml; charset=utf-8',
						),					
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'test'	=> FALSE,
					'post_url' => 'https://www.eloanserver.com/purposecashadvance/marketing/marketingpost.asmx',
				),
			),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
		);
	
	protected $static_thankyou = FALSE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		if ($params['iaw2_m8945'] === true) { // Mantis #8945 [DY] // Never use $this->params['iaw2_m8945'] here.
			return $this->Generate_Fields_for_IAW2($lead_data, $params);
		}
	
		//Fields that will always be blank:
        //jobtitle
        //supervisor
        //datehired_month
        //datehired_year
        //months_at_Address

        $fields = array (
                "firstname"          => substr($lead_data['data']['name_first'],0,25),
                "lastname"           => substr($lead_data['data']['name_last'],0,25),                
                "address"            => substr($lead_data['data']['home_street'],0,50),
                "city"               => substr($lead_data['data']['home_city'],0,50),
                "state"              => $lead_data['data']['home_state'],
                "zip"                => $lead_data['data']['home_zip'],
                "email"              => substr($lead_data['data']['email_primary'],0,50),
                "homephonenumber"    => $lead_data['data']['phone_home'],
                "takehomepay"        => $lead_data['data']['income_monthly_net'],
                "ssn"                => $lead_data['data']['ssn_part_1']. 
                                        $lead_data['data']['ssn_part_2']. 
                                        $lead_data['data']['ssn_part_3'],
                "checkdeposit"       => "DEPOSIT"
        );
        
        //DOB
        $dob_parts = explode("/",$lead_data['data']['dob']);
        $fields["dob_month"] = $dob_parts[0];
        $fields["dob_day"] = $dob_parts[1];
        if(strlen($dob_parts[2]) > 2)
        {
        	$fields["dob_year"] = substr($dob_parts[2],2);
        }
        else
        {
        	$fields["dob_year"] = $dob_parts[2];
        }
        
        //Bank Account Type
        if(isset($lead_data['data']['bank_account_type']) &&
           $lead_data['data']['bank_account_type'] != "")
            $fields["accounttype"] = strtoupper(substr($lead_data['data']['bank_account_type'],0,1)) .
                                     strtolower(substr($lead_data['data']['bank_account_type'],1));
                
        //Work Phone
        if(isset($lead_data['data']['phone_work']) &&
           $lead_data['data']['phone_work'] != "")
            $fields["employerphonenumber"] = $lead_data['data']['phone_work'];

        //Cell Phone
        if(isset($lead_data['data']['phone_cell']) &&
           $lead_data['data']['phone_cell'] != "")
            $fields["celnumber"] = $lead_data['data']['phone_cell'];

        //Income Type
        if(isset($lead_data['data']['income_type']) &&
           $lead_data['data']['income_type'] != "")
        {
        	$fields["currentlyemployed"]  = (strtolower($lead_data['data']['income_type']) == 'employment') ? 'Yes' : 'No';
        }
            
        //Bank ABA
        if(isset($lead_data['data']['bank_aba']) &&
           $lead_data['data']['bank_aba'] != "")
        {
        	$fields["bankabarouting"]  = $lead_data['data']['bank_aba'];
            $fields["bankaccnumber"]   = $lead_data['data']['bank_account'];
            $fields["bankname"]        = $lead_data['data']['bank_name'];
        }
                
        //Employer Name
        if(isset($lead_data['data']['employer_name']) &&
           $lead_data['data']['employer_name'] != "")
            $fields["companyname"]    = substr($lead_data['data']['employer_name'],0,50);

        //State-issued ID
        if(isset($lead_data['data']['state_id_number']) &&
           $lead_data['data']['state_id_number'] != "")
        {
            $issued_state = ($lead_data['data']['state_issued_id']) ? $lead_data['data']['state_issued_id'] : $lead_data['data']['home_state'];
            $fields["license"]        = substr($lead_data['data']['state_id_number'],0,20);
            $fields["licensestate"]   = $issued_state;
        }
        
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
        
        if(isset($freq))
        {
            //convert income frequency to requested format
            switch($freq)
            {
                case 'WEEKLY':
                    $income_frequency = 'Weekly';
                    break;
                case 'BIWEEKLY':
                case 'BI_WEEKLY':
                    $income_frequency = 'BiWeekly';
                    break;
                case 'TWICE_MONTHLY':
                    $income_frequency = 'Twice Monthly';
                    break;
                case 'MONTHLY':
                	$income_frequency = 'Monthly';
                	break;
            }
            $fields["payperiod"] = $income_frequency;
        }
        
        //Paydates
        if(isset($lead_data['data']['paydates']))
        {
        	$fields["paydate1_month"]    = date("m",strtotime($lead_data['data']['paydates'][0]));
            $fields["paydate1_day"]      = date("d",strtotime($lead_data['data']['paydates'][0]));
            $fields["paydate1_year"]     = date("y",strtotime($lead_data['data']['paydates'][0]));
            $fields["paydate2_month"]    = date("m",strtotime($lead_data['data']['paydates'][1]));
            $fields["paydate2_day"]      = date("d",strtotime($lead_data['data']['paydates'][1]));
            $fields["paydate2_year"]     = date("y",strtotime($lead_data['data']['paydates'][1]));
        }
                
        //References
        if(isset($lead_data['data']['ref_01_name_full']) &&
           $lead_data['data']['ref_01_name_full'] != "")
        {
            $fields["reference_name1"]    = substr($lead_data['data']['ref_01_name_full'],0,101);
            $fields["reference_phone1"]   = $lead_data['data']['ref_01_phone_home'];
            $fields["reference_name2"]    = substr($lead_data['data']['ref_02_name_full'],0,101);
            $fields["reference_phone2"]   = $lead_data['data']['ref_02_phone_home'];
        }        
                
     
        return $fields;
            
	}
	
	/**
	 * Post function for New Cash Advance Product InterADworks (iaw2).
	 * See sample soap code in TCF.
	 * 
	 * 04/08/2008 EDITED for GForge [#10907] Updated Posting Specs - InterADworks (iaw2) [TF]
	 *
	 * 04/08/2008 EDITED for GForge [#10907] Updated Posting Specs - InterADworks (iaw2) [TF]
	 *
	 * @link http://bugs.edataserver.com/view.php?id=8945 New Cash Advance Product InterADworks (iaw2)
	 * @param array $lead_data
	 * @param array $params
	 * @return string XML doc
	 */
	private function Generate_Fields_for_IAW2(&$lead_data, &$params) {
        
		$params2 = array(); // used by next line only.
        $fields = $this->Generate_Fields($lead_data, $params2); //this insanity brought to you by Demin
        
		$dob = ($lead_data['data']['dob']) ? $lead_data['data']['dob'] :
			"{$lead_data['data']['date_dob_m']}/{$lead_data['data']['date_dob_d']}/{$lead_data['data']['date_dob_y']}";
			
		if (isset($lead_data['data']['paydates'])) {
			$paydate_1 = date("m/d/Y",strtotime($lead_data['data']['paydates'][0]));
			
			$paydate_1_m = date("m",strtotime($lead_data['data']['paydates'][0]));
			$paydate_1_d = date("d",strtotime($lead_data['data']['paydates'][0]));
			$paydate_1_y = date("Y",strtotime($lead_data['data']['paydates'][0]));
			
			$paydate_2_m = date("m",strtotime($lead_data['data']['paydates'][1]));
			$paydate_2_d = date("d",strtotime($lead_data['data']['paydates'][1]));
			$paydate_2_y = date("Y",strtotime($lead_data['data']['paydates'][1]));
			
			$paydate_2 = date("m/d/Y",strtotime($lead_data['data']['paydates'][1]));
		} else { // impossible case
			$paydate_1 = '01/01/1970'; // for safe purpose
			$paydate_2 = '01/01/1970'; 
		}
		
		if ($params['test'] === TRUE) {
			$test = 'True';
		} else {
			$test = 'False';			
		}
		
		$is_military = (strcasecmp($lead_data['data']['military'], 'TRUE') === 0) ? 'YES' : 'NO';
        
		$dom = new DOMDocument('1.0','utf-8');
		$soap_envelope_element = $dom->createElement('soap:Envelope');
		$soap_envelope_element->setAttribute('xmlns:xsi',"http://www.w3.org/2001/XMLSchema-instance");
		$soap_envelope_element->setAttribute('xmlns:xsd',"http://www.w3.org/2001/XMLSchema");
		$soap_envelope_element->setAttribute('xmlns:soap',"http://schemas.xmlsoap.org/soap/envelope/");
		
		$dom->appendChild($soap_envelope_element);
		
		$soap_body_element = $dom->createElement('soap:Body');
		$soap_envelope_element->appendChild($soap_body_element);
		
		$post_element = $dom->createElement('Post');
		$post_element->setAttribute('xmlns',"MarketingPost");
		$soap_body_element->appendChild($post_element);

		$elements = array();
		$elements[] = $dom->createElement('Test', $test);		
		$elements[] = $dom->createElement('ADID', $params['adid']);
//		if (empty($params['iaw_g9636']))
//		{
//			$elements[] = $dom->createElement('CharID', '');
//		}		
		$elements[] = $dom->createElement('jobtitle', '');	// N/A	
		$elements[] = $dom->createElement('supervisor', '');		// N/A	
		$elements[] = $dom->createElement('address', $lead_data['data']['home_street']);		
		$elements[] = $dom->createElement('bankabarouting', $lead_data['data']['bank_aba']);		
		$elements[] = $dom->createElement('bankaccnumber', $lead_data['data']['bank_account']);		
		$elements[] = $dom->createElement('bankname', $lead_data['data']['bank_name']);		
		$elements[] = $dom->createElement('checkdeposit', 'DEPOSIT');
		$elements[] = $dom->createElement('city', $lead_data['data']['home_city']);		
		$elements[] = $dom->createElement('companyname', $lead_data['data']['employer_name']);		
		$elements[] = $dom->createElement('dob_month', $lead_data['data']['date_dob_m']);
		$elements[] = $dom->createElement('dob_day', $lead_data['data']['date_dob_d']);
		$elements[] = $dom->createElement('dob_year', $lead_data['data']['date_dob_y']);
		
		$elements[] = $dom->createElement('email', $lead_data['data']['email_primary']);		
		$elements[] = $dom->createElement('firstname', $lead_data['data']['name_first']);
//		if (!empty($params['iaw_g9636']))
//		{
//			$elements[] = $dom->createElement('MiddleInitial', '');
//		}		
		$elements[] = $dom->createElement('homephonenumber', $lead_data['data']['phone_home']);		
		$elements[] = $dom->createElement('lastname', $lead_data['data']['name_last']);	
		$elements[] = $dom->createElement('license', $fields["license"]);		
		$elements[] = $dom->createElement('licensestate', $fields["licensestate"]);
		
		$elements[] = $dom->createElement('paydate1_month', $paydate_1_m);
		$elements[] = $dom->createElement('paydate1_day', $paydate_1_d);
		$elements[] = $dom->createElement('paydate1_year', $paydate_1_y);
		
		$elements[] = $dom->createElement('paydate2_month', $paydate_2_m);
		$elements[] = $dom->createElement('paydate2_day', $paydate_2_d);
		$elements[] = $dom->createElement('paydate2_year', $paydate_2_y);
		
		$elements[] = $dom->createElement('reference_name1', $lead_data['data']['ref_01_name_full']);
		$elements[] = $dom->createElement('reference_phone1', $lead_data['data']['ref_01_phone_home']);
		$elements[] = $dom->createElement('reference_name2', $lead_data['data']['ref_02_name_full']);
		$elements[] = $dom->createElement('reference_phone2', $lead_data['data']['ref_02_phone_home']);
				
		$elements[] = $dom->createElement('ssn', $fields["ssn"]);
		$elements[] = $dom->createElement('state', $lead_data['data']['home_state']);
		$elements[] = $dom->createElement('zip', $lead_data['data']['home_zip']);
		$elements[] = $dom->createElement('takehomepay', $lead_data['data']['income_monthly_net']);
		
		$elements[] = $dom->createElement('employerphonenumber', ''); // unknown
		$elements[] = $dom->createElement('accounttype', ucfirst($fields["accounttype"]));
		$elements[] = $dom->createElement('payperiod', $fields["payperiod"]);
		$elements[] = $dom->createElement('celnumber', $lead_data['data']['phone_cell']); //their post doc, not my typo
		$elements[] = $dom->createElement('currentlyemployed', $fields["currentlyemployed"]);
		
		$elements[] = $dom->createElement('datehired_month', date("m", strtotime($lead_data['data']['date_of_hire'])));
		$elements[] = $dom->createElement('datehired_year', date("Y", strtotime($lead_data['data']['date_of_hire'])));
		
		$elements[] = $dom->createElement('months_at_Address', $lead_data['data']['months_at_residence']);
		
		
		$elements[] = $dom->createElement('IsMilitary', $is_military);
				
		/*$elements[] = $dom->createElement('CurrentlyEmployed', $fields["currentlyemployed"]);
		$elements[] = $dom->createElement('DateHired', '');		// N/A	
		$elements[] = $dom->createElement('DateMovedIn', '');		// N/A
		$elements[] = $dom->createElement('PayPeriod', $fields["payperiod"]);
				
		$elements[] = $dom->createElement('FirstPayDay', $paydate_1);		
		$elements[] = $dom->createElement('SecondPayDay', $paydate_2);		
		$elements[] = $dom->createElement('SSN', $fields['ssn']);		
				
				
		$elements[] = $dom->createElement('TakeHomePay', $lead_data['data']['income_monthly_net']);		
		$elements[] = $dom->createElement('WorkPhone', $lead_data['data']['phone_work']);		
		$elements[] = $dom->createElement('AcctType', $fields["accounttype"]);
		*/
		
		
//		if (!empty($params['iaw_g9636']))
//		{			
//			$elements[] = $dom->createElement('Military', $is_military);
//		}
		
		foreach ($elements as $element) {
			$post_element->appendChild($element);
		}
		
		$fields_xml = $dom->saveXML();

		return $fields_xml;     		
	}

	public function Generate_Result(&$data_received, &$cookies)
	{
		if ($this->params['iaw2_m8945'] === true) { // Mantis #8945 [DY]
			return $this->Generate_Result_for_IAW2($data_received, $cookies);
		}
		
		$result = new Vendor_Post_Result();
		
		if (!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		elseif (preg_match ('/<responseCode>ACCEPT<\/responseCode>/i', $data_received, $d))
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
	
	/**
	 * Handle response message for New Cash Advance Product InterADworks (iaw2).
	 *
	 * @link http://bugs.edataserver.com/view.php?id=8945 New Cash Advance Product InterADworks (iaw2)
	 * @param string $data_received
	 * @param mixed $cookies
	 * @return string
	 */
	public function Generate_Result_for_IAW2(&$data_received, &$cookies) {
		
		$result = new Vendor_Post_Result();
		
		if (!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		elseif (preg_match ('/<Decision>ACCEPT<\/Decision>/i', $data_received, $d))
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content( $data_received ) );
			$result->Set_Vendor_Decision('ACCEPTED');
		}
		else // <Decision>REJECT</Decision>
		{
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
		}

		return $result;		
	}

//	Uncomment the next line to use HTTP GET instead of POST
//	public static function Get_Post_Type() {return Http_Client::HTTP_GET;}

	public function __toString()
	{
		return "Vendor Post Implementation [IAW]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		if ($this->params['iaw2_m8945'] === true) { // Mantis #8945 [DY]
			return $this->Thank_You_Content_for_IAW2($data_received);
		}
		
		$matches = array();
        preg_match('/\[CDATA\[ ?(http[^\]]+) ?\]/i', $data_received, $matches);
		$content = parent::Generic_Thank_You_Page($matches[1], self::REDIRECT);
		return($content);
	}
	
	/**
	 * Enter description here...
	 *
	 * Reference: See Vendor_Post_Impl_NTL::Thank_You_Content().
	 * 
	 * @link http://bugs.edataserver.com/view.php?id=8945 New Cash Advance Product InterADworks (iaw2)
	 * @param string $data_received
	 * @return string
	 */
	public function Thank_You_Content_for_IAW2(&$data_received)
	{
		// https://www.ffscdev.com/purposecashadvance/application/applicationpage3.aspx?SessionID=66981043588792007626
		$url = '';
		if(preg_match('!<RedirectUrl>([^<]+)?</RedirectUrl>!i', $data_received, $match))
		{
			//Since they're sending back XML, we need to decode some entities
			//like &amp;s so that it doesn't screw up the auto redirect.
			$url = html_entity_decode(trim($match[1]));
		}
		
        $content = (!empty($url)) ? parent::Generic_Thank_You_Page($url, self::REDIRECT) : FALSE;
        return $content;
	}	
}
?>
