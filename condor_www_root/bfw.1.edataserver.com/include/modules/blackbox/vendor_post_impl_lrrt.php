<?php

/**
 * A concrete implementation class for posting to LeadRev (campaign lrrt)
 * NOTE: this campaign intentionally accepts everything sent to it
 * NOTE2: this campaign posts to a bogus url, then accepts all
 * NOTE3: the lead info is added to the redirect
 */
 class Vendor_Post_Impl_LRRT extends Abstract_Vendor_Post_Implementation
 {

 	/**
 	 * Must be passed as 2nd arg to Generic_Thank_You_Page: parent::Generic_Thank_You_Page($url, self::REDIRECT);
 	 *
 	 */
 	 const REDIRECT = 4;

 	 protected $rpc_params  = Array(
	 	 // Params which will be passed regardless of $this->mode
	 	 'ALL'     => Array(
	 	      'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php', //http://www.yourfinancialoptions.net/default.asp
	 	 ),
        'lrrt' => Array(
            'ALL' => Array(
                'partner' => '2',
	 	         ),
            ),
        'lrrt2' => Array(
            'ALL' => Array(
                'partner' => '4',
                 ),
            ),
        );

 	 
 	 //for lrrt ONLY:
 	 protected $lrrt_args="";
 	 
 	 protected $static_thankyou = FALSE;

 	 /**
 	  * Generate field values for post request.
 	  *
 	  * @param array $lead_data User input data.
 	  * @param array $params Values from $this->rpc_params.
 	  * @return array Field values for post request.
 	  */
 	  public function Generate_Fields(&$lead_data, &$params)
 	  {

 	  	$_tmp['dob'] = $lead_data['data']['date_dob_m'].'/'.$lead_data['data']['date_dob_d'].'/'.$lead_data['data']['date_dob_y'];
 	  	//$_tmp['over18'] = (strtotime($_tmp['dob']) < strtotime('-18 years')) ? 'Y' : 'N';

 	  	//parse the phone(s) into 3 separate lumps @TF
 	  	$ltemp = $lead_data['data']['phone_home'];
 	  	$_tmp['ph_area_code'] = substr($ltemp,0,3);
 	  	$_tmp['ph_prefix'] = substr($ltemp,3,3);
 	  	$_tmp['ph_exchange'] = substr($ltemp,6,4);


 	  	$ltemp2 = $lead_data['data']['phone_work'];
 	  	$_tmp['ph2_area_code'] = substr($ltemp2,0,3);
 	  	$_tmp['ph2_prefix'] = substr($ltemp2,3,3);
 	  	$_tmp['ph2_exchange'] = substr($ltemp2,6,4);
 	  	
 	  	$_tmp['uni_ssn'] = $lead_data['data']['ssn_part_1'] . $lead_data['data']['ssn_part_2'] . $lead_data['data']['ssn_part_3'];



 	  	/*customer.bankAccount.accountType
 	  	 "1" -> Pension
 	  	 "2" -> Disability Benefits
 	  	 "3" -> Social Security
 	  	 "4" -> Unemployed
 	  	 "5" -> Employed
 	  	 "6" -> Temp Agency
 	  	 "7" -> Military*/

 	  	if(strcasecmp($lead_data['data']['income_type'], "EMPLOYMENT")==0){
 	  		$_tmp['emp_full_part']='Full Time';
 	  		$_tmp['income_source']='5';
 	  	}
 	  	else{
 	  		$_tmp['emp_full_part']='Unemployed';
 	  		$_tmp['income_source']='4';
 	  	}


 	  	$_tmp['income_bin'] = $lead_data['data']['income_type'];

 	  	$_tmp['nextpaydate'] = date("m/d/Y", strtotime(reset($lead_data['data']['paydates'])));

 	  	// second paydate @TF
 	  	$_tmp['secondpaydate'] = date("m/d/Y", strtotime(next($lead_data['data']['paydates'])));

 	  	//~~~

 	  	if (!empty($lead_data['data']['paydate']['frequency']))
 	  	$_tmp['payrollfreq'] = $lead_data['data']['paydate']['frequency'];
 	  	elseif (!empty($lead_data['data']['income_frequency']))
 	  	$_tmp['payrollfreq'] = $lead_data['data']['income_frequency'];
 	  	elseif (!empty($lead_data['data']['paydate_model']['income_frequency']))
 	  	$_tmp['payrollfreq'] = $lead_data['data']['paydate_model']['income_frequency'];
 	  	else
 	  	$_tmp['payrollfreq'] = ''; // NO such type, but this is a required field.
 	  	
 	  	
 	  	
 	  	// $_tmp['payrollfreq_2'] Options are “Weekly”, “Bi-Weekly”, “Semi-Monthly” or “Monthly”
 	  	$_tmp['payrollfreq_2']=ucfirst(strtolower($_tmp['payrollfreq']));
 	  	
 	  	if (strcasecmp($_tmp['payrollfreq'], 'BI_WEEKLY')==0)  {
 	  		$_tmp['payrollfreq_2']='Bi-Weekly';
 	  	}
 	  	
 	  	if (strcasecmp($_tmp['payrollfreq'], 'TWICE_MONTHLY')==0)  {
 	  		$_tmp['payrollfreq_2']='Semi-Monthly';
 	  	}
 	  	
 	  	// $_tmp['call_time_ucf'] CUSTCONTACTTIME	“Morning”, “Afternoon”, “Evening”, or “Anytime”
 	  	$_tmp['call_time_ucf']=ucfirst(strtolower($lead_data['data']['best_call_time']));


 	  	switch (strtolower($lead_data['data']['bank_account_type'])) {
 	  		case 'checking':
 	  			$_tmp['bankaccttype'] = 'Checking';
 	  			break;
 	  		case 'savings':
 	  			$_tmp['bankaccttype'] = 'Savings';
 	  			break;
 	  		default:
 	  			$_tmp['bankaccttype'] = ''; // NO such type, but this is a required field.
 	  			break;
 	  	}
 	  			
 	  	if (substr_count($lead_data['dep_account'],'DD')>0)  {
 	  		$_tmp['direct_deposit'] = 1;
 	  	}
 	  	else  {
 	  		$_tmp['direct_deposit'] = 0;
 	  	}
 	  	
 	  	if (strcasecmp($lead_data['military'], 'FALSE')==0)  {
 	  		$_tmp['mil_digit']=0;
 	  	}
 	  	else  {
 	  		$_tmp['mil_digit']=1;
 	  	}

/* 	  			lrrt INCOME_TYPE
 * 				P = Full Time
 *	  			G = Social Security
 *	  			M = Military
 *	  			W = Welfare
 *	  			D = Disability
 *	  			S = Pension
 *	  			L = Self Employed
 *	  			U = Unemployment
*/
 	  switch (strtolower($lead_data['data']['income_type'])) {
 	  		case 'employment':
 	  			$_tmp['incometype'] = 'P';
 	  			break;
 	  		case 'benefits':
 	  			$_tmp['incometype'] = 'D';
 	  			break;
 	  		default:
 	  			$_tmp['incometype'] = 'U';
 	  			break;
 	  	} 	  	

 	  	$fields = array (
 	  		
 	  	//'pub' => '100113',
 	  	'SOURCEID' => '40',
 	  	'PARTNER' => $params['partner'],
 	  	'UNIQUEID' => $lead_data['data']['unique_id'],
 	  		
 	  	'WEBSITENAME' => $lead_data['data']['client_url_root'],
 	  	'TITLE'=> $lead_data['data'][''],
 	  		
 	  	'FIRST_NAME' => $lead_data['data']['name_first'],
 	  	'CUSTOMER_NAME' => $lead_data['data']['name_last'],
 	  	'ZIP_CODE' => $lead_data['data']['home_zip'],
 	  	'PHONE_NUM1' => $lead_data['data']['phone_home'],
 	  	'CELL_PHONE' => $lead_data['data']['phone_cell'],
 	  	'EMAIL_ADDRESS' => $lead_data['data']['email_primary'],
 	  	'DIRECT_DEPOSIT' => $_tmp['direct_deposit'], //0 or 1
 	  	'BANK_NAME' => $lead_data['data']['bank_name'],
 	  	'BANK_ROUTING_NUMBER' => $lead_data['data']['bank_aba'],
 	  	'BANK_ACCOUNT_NUMBER' => $lead_data['data']['bank_account'],
 	  	'BANK_ACCOUNT_TYPE' => $_tmp['bankaccttype'],  //Checking or Savings
 	  	'SSN' => $_tmp['uni_ssn'],	//dashless
 	  	'ADDRESS1' => $lead_data['data']['home_street'],
 	  	'CITY' => $lead_data['data']['home_city'],
 	  	'STATE' => $lead_data['data']['home_state'],
 	  	'OWNERSHIP_DESCRIPTION' => ucfirst(strtolower($lead_data['data']['residence_type'])), //Rent Own
 	  	'MONTHLY_HOUSING_EXPENSE' => '', //monthly rent or mortgage amount **********
 	  	'DLNUMBER' => $lead_data['data']['state_id_number'],
 	  	'DLSTATE' => $lead_data['data']['state_issued_id'],
 	  	'DOB' => $_tmp['dob'],
 	  	'EMPLOYER_NAME' => $lead_data['data']['employer_name'],
 	  	'OCCUPATION_DESCRIPTION' => $lead_data['data'][''], //job title?
 	  	'ACTIVE_MILITARY' => $_tmp['mil_digit'],  //1 for yes  or 0
 	  	'YEARS_IN_POSITION' => '',  //**********************************************
 	  	'MONTHS_IN_POSITION' => '', //**********************************************
 	  	'YEARS_AT_ADDRESS' => '',   //**********************************************
 	  	'MONTHS_AT_ADDRESS' => '',  //**********************************************
 	  	'EMPLOYMENTARRANGEMENT' => $_tmp['emp_full_part'], //“Full Time”, “Part Time”, or “Unemployed”
 	  	'EMPLOYER_ADDRESS' => 'unknown', //**********************************************
 	  	'EMPLOYER_CITY' => 'unknown',	//**********************************************
 	  	'EMPLOYER_STATE' => 'unknown',	//**********************************************
 	  	'EMPLOYER_ZIP' => 'unknown',	//**********************************************
 	  	'PHONE_NUM2' => $lead_data['data']['phone_work'],  // work phone
 	  	'INCOME' => $lead_data['data']['income_monthly_net'],  //monthly total income
 	  	'PAYCHECK_AMOUNT' => 'unknown',  //******************************************
 	  	'PAYDAYFREQUENCY' => $_tmp['payrollfreq_2'],  //Options are “Weekly”, “Bi-Weekly”, “Semi-Monthly” or “Monthly
 	  	'CUSTCONTACTTIME' => $_tmp['call_time_ucf'],		//“Morning”, “Afternoon”, “Evening”, or “Anytime
 	  	'INCOME_TYPE' => $_tmp['incometype'],
 	  	'NEXTPAYDATE' => $_tmp['nextpaydate'],
 	  	'SECONDPAYDATE' => $_tmp['secondpaydate'],
 	  	'REF1_NAME' => $lead_data['data']['ref_01_name_full'],
 	  	'REF1_RELATIONSHIP' => $lead_data['data']['ref_01_relationship'],
 	  	'REF1_PHONE' => $lead_data['data']['ref_01_phone_home'],
 	  	'REF2_NAME' => $lead_data['data']['ref_02_name_full'],
 	  	'REF2_RELATIONSHIP' => $lead_data['data']['ref_02_relationship'],
 	  	'REF2_PHONE' => $lead_data['data']['ref_02_phone_home'],
 	  	
 	  	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 	  		
 	  	);
 	  	
 	  	//GF 5219 Cash Loan Network SL 4 Tracking [TF] (hack warning)
 	  	if(stristr($lead_data['data']['client_url_root'],"cashloannetwork.com")!==FALSE){
 	  		//the lead source is cashloannetwork, overwrite a couple fields 
 	  		$fields['PARTNER'] = '5';
 	  		$fields['auth']="E8176263-6757-46FA-8F47-2F35A412CFB7";
 	  	}
 	  	
		//@TF lrrt ONLY:
 	  	$this->lrrt_args = http_build_query($fields);
 	  	
 	  	return $fields;
 	  }

 	  /**
 	   * Generate post request results.
 	   *
 	   * @param string $data_received Data received after post request is sent.
 	   * @param unknown $cookies a useless parameter.
 	   * @return object a Vendor_Post_Result object.
 	   */
 	   public function Thank_You_Content(&$data_received)
 	   {
 	   	$matches = array();
 	   	//preg_match('/(http.*ACCEPTED)/', $data_received, $matches);
 	   	//if(preg_match('!<redirectURL>([^<]+)?</redirectURL>!i', $data_received, $match))
 	   	
 	   		$url = ('http://www.yourfinancialoptions.net/default.asp?' . $this->lrrt_args);
 	   		//$url = str_replace("&amp;","&",$url);
 	   		//echo "at thank you <BR />";

 	   	$content = parent::Generic_Thank_You_Page($url, self::REDIRECT);
 	   	return($content);
 	   }

 	   public function Generate_Result(&$data_received, &$cookies)
 	   {
 	   	$result = new Vendor_Post_Result();

// 	   	if (!strlen($data_received))
// 	   	{
// 	   		$result->Empty_Response();
// 	   		$result->Set_Vendor_Decision('TIMEOUT');
// 	   	}
// 	   	elseif(preg_match('!<responseCode>OK</responseCode>!i', $data_received))
// 	   	{
 	   		$result->Set_Message("Accepted");
 	   		$result->Set_Success(TRUE);
 	   		$result->Set_Thank_You_Content( self::Thank_You_Content($data_received));
 	   		$result->Set_Vendor_Decision('ACCEPTED');
// 	   	}
// 	   	else
// 	   	{
// 	   		$result->Set_Message("Rejected");
// 	   		$result->Set_Success(FALSE);
// 	   		$result->Set_Vendor_Decision('REJECTED');
// 	   	}

 	   	return $result;
 	   }

 	   

 	   /**
 	    * A PHP magic function.
 	    *
 	    * @link http://www.php.net/manual/en/language.oop5.magic.php
 	    * @return string a string describing this class.
 	    */
 	    public function __toString()
 	    {
 	    	return "Vendor Post Implementation [lrrt]";
 	    }

 	    /**
 	     * Return a "Thank You" message back.
 	     *
 	     * @param string $data_received URL used for redirecting.
 	     * @return string a "Thank You" message.
 	     */
 	     /*public function Thank_You_Content($data_received)
 	      {
 	      //$url=urldecode($data_received);
 	      $url=html_entity_decode($data_received);
 	      $content = parent::Generic_Thank_You_Page($url, self::REDIRECT);

 	      return $content;
 	      }*/

 }
