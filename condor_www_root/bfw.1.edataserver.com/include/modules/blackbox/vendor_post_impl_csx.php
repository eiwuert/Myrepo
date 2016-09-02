<?php

/**
 * @desc A concrete implementation class to post to CSX (Cash-X)
 */
class Vendor_Post_Impl_CSX extends Abstract_Vendor_Post_Implementation
{
	
	// must be passed as 2nd arg to Generic_Thank_You_Page:
	//		parent::Generic_Thank_You_Page($url, self::REDIRECT);
	const REDIRECT = 2;

	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
			  	'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/CSX',
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				//'post_url' =>   'https://www.cashinterchange.com/leadpost.asp'
				'post_url' =>   'https://www.cashinterchange.com/support.asp?WCI=ProcessLeads'
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'csx'    => Array(
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
		
		$ageToCompare  = mktime(0, 0, 0, date("m")  , date("d"), date("Y")-18);
		$ageGreaterThanX = ($lead_data['data']['dob'] < $ageToCompare) ? 'y' : 'n';
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
            }
//            $fields["payperiod"] = $income_frequency;
        }
		
		
        $ref1Array = split(" ", $lead_data['data']['ref_01_name_full']);
        $ref1FirstName = $ref1Array[0];
        		$ref1LastName = "";
        for($i = 1; $i < count($ref1Array); $i++){
        	if($i > 1){
				
        		$ref1LastName .= " ";
        	}
        	$ref1LastName .= $ref1Array[$i];
        }
        $ref2Array = split(" ", $lead_data['data']['ref_02_name_full']);
        $ref2FirstName = $ref2Array[0];
        		$ref2LastName = "";
        for($i = 1; $i < count($ref2Array); $i++){
        	if($i > 1){
				
        		$ref2LastName .= " ";
        	}
        	$ref2LastName .= $ref2Array[$i];
        }
		
		
		$fields = array (
'first-name' => $lead_data['data']['name_first']
,
//'middle-name' => ""      // Unknown
//,
'last-name' => $lead_data['data']['name_last']
,
'e-mail' => $lead_data['data']['email_primary']
,
'home-phone' => $lead_data['data']['phone_home']
,
'work-phone' => $lead_data['data']['phone_work']
,
'address' => $lead_data['data']['home_street'] .' '. $lead_data['data']['home_unit']
,
'city' => $lead_data['data']['home_city']
,
'state' => $lead_data['data']['home_state']
,
'zip' => $lead_data['data']['home_zip']
,
'direct_deposit' => ($lead_data['data']['income_direct_deposit'] == 'TRUE') ? 'y' : 'n'
,
'monthly_net_income' => $lead_data['data']['income_monthly_net']
,
'how_often_paid ' => $lead_data['data']['income_frequency']
,
'next_pay_date_1' => date("m/d/Y", strtotime($lead_data['data']['paydates'][0]))
,
'next_pay_date_2' => date("m/d/Y", strtotime($lead_data['data']['paydates'][1]))
,
//'(y/n) US citizen --- don\\\'t think we collect this' => ""      // Unknown
//,
'date_of_birth' => $lead_data['data']['date_dob_m'] . "/" . $lead_data['data']['date_dob_d'] . "/" . $lead_data['data']['date_dob_y']
,
'at_least_18_years_old ' => $ageGreaterThanX
,
'company_name ' => $lead_data['data']['employer_name']
,
'company_at_least_3_months' => ($lead_data['data']['employer_length'] ? "y" : "n")
,
'ssn' => $lead_data['data']['social_security_number']
,
'drivers_license' => $lead_data['data']['state_id_number']
,
'state_id ' => $lead_data['data']['state_issued_id']
,
'income_type' => (strtolower($lead_data['data']['income_type']) == 'employment') ? 'Employed' : 'Benefits'
,
'bank_name ' => $lead_data['data']['bank_name']
,
'aba_number' => $lead_data['data']['bank_aba']
,
'account_number' => $lead_data['data']['bank_account']
,
'ref_1_fname' => $ref1FirstName
,
'ref_1_lname' => $ref1LastName
,
'ref_1_phone' => $lead_data['data']['ref_01_phone_home']
,
'ref_1_rel' => $lead_data['data']['ref_01_relationship']
,
'ref_2_fname' => $ref2FirstName
,
'ref_2_lname' => $ref2LastName
,
'ref_2_phone' => $lead_data['data']['ref_02_phone_home']
,
'ref_2_rel' => $lead_data['data']['ref_02_relationship']
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
		elseif (preg_match ('/<accepted_status>1<\/accepted_status>/i', $data_received, $d))
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content( $data_received ) );
			$result->Set_Vendor_Decision('ACCEPTED');
		}
		else
		{
			preg_match('/<message>([^<]+)<\/message>/i', $data_received, $m);
			
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
			$result->Set_Vendor_Reason($m[1]);
		}
		return($result);
	}

//	Uncomment the next line to use HTTP GET instead of POST
//	public static function Get_Post_Type() {return Http_Client::HTTP_GET;}

	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [CSX]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		preg_match('/<redirect>([^<]+)<\/redirect>/i', $data_received, $m);
		$url = trim($m[1]);
		$content = parent::Generic_Thank_You_Page($url, self::REDIRECT);
		return($content);
	}
	
	
}
