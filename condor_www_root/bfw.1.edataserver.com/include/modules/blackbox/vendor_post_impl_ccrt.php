<?php

/**
 * A concrete implementation class for posting to Monetizit, LLC (campaign ccrt)
 */
class Vendor_Post_Impl_CCRT extends Abstract_Vendor_Post_Implementation
{

	/**
	 * Must be passed as 2nd arg to Generic_Thank_You_Page: parent::Generic_Thank_You_Page($url, self::REDIRECT);
	 *
	 */
	const REDIRECT = 4;

	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
			    'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/CCRT',
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				'post_url' => 'https://installments.securetransactionscorp.com/begin.do?transition=finalize&view=signup&k=af7rl4gwpx&p=CD46'
				),
			'RC'      => Array(
				'post_url' => 'https://installments.securetransactionscorp.com/begin.do?transition=finalize&view=signup&k=fd1b16pf6g&p=CD46'
				),
			'LIVE'    => Array(
				'post_url' => 'https://installments.securetransactionscorp.com/begin.do?transition=finalize&view=signup&k=fd1b16pf6g&p=CD46'
				)
		);

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
		
		
		
		/*customer.bankAccount.accountType
		"1" -> Pension
		"2" -> Disability Benefits
		"3" -> Social Security
		"4" -> Unemployed
		"5" -> Employed
		"6" -> Temp Agency
		"7" -> Military*/

		if(strcasecmp($lead_data['data']['income_type'], "EMPLOYMENT")==0){
			$_tmp['income_source']='5';
		}
		else{
			$_tmp['income_source']='4';
		}
		
		
		$_tmp['income_bin'] = $lead_data['data']['income_type'];
		
		//@@ToDo ++++++++++++++++  direct deposit, java.lang.Boolean.TRUE
		$_tmp['payrolltype'] = (strtolower($lead_data['data']['income_direct_deposit']) == 'true') ? 'java.lang.Boolean.TRUE' : 'java.lang.Boolean.FALSE';

		$_tmp['nextpaydate'] = date("m/d/Y", strtotime(reset($lead_data['data']['paydates'])));
		
		// second paydate @TF
		$_tmp['secondpaydate'] = date("m/d/Y", strtotime(next($lead_data['data']['paydates'])));

		$_tmp['optin'] = (strtolower($lead_data['data']['mh_offer']) == 'true') ? 'yes' : 'no';
		
		//added 07/27/07 due to spec change ~~~
		
		$map_payperiod = array(
			'WEEKLY' => '4',
			'BI_WEEKLY' => '3',
			'TWICE_MONTHLY' => '2',
			'MONTHLY' => '1',
		);
		
		$_tmp['ccrt_paydate'] = $map_payperiod[$lead_data['data']['paydate_model']['income_frequency']];
		//~~~

		if (!empty($lead_data['data']['paydate']['frequency']))
			$_tmp['payrollfreq'] = $payperiod[$lead_data['data']['paydate']['frequency']];
		elseif (!empty($lead_data['data']['income_frequency']))
			$_tmp['payrollfreq'] = $payperiod[$lead_data['data']['income_frequency']];
		elseif (!empty($lead_data['data']['paydate_model']['income_frequency']))
			$_tmp['payrollfreq'] = $payperiod[$lead_data['data']['paydate_model']['income_frequency']];
		else
			$_tmp['payrollfreq'] = ''; // NO such type, but this is a required field.

		

		switch (strtolower($lead_data['data']['income_type'])) {
			case 'employment':
				$_tmp['incometype'] = 'P';
				break;
			case 'benefits':
				$_tmp['incometype'] = 'W';
				break;
			default:
				$_tmp['incometype'] = 'O';
				break;
		}

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

		$fields = array (
			
			//'pub' => '100113',
			'customer.firstName' => $lead_data['data']['name_first'],
			'customer.lastName' => $lead_data['data']['name_last'],
			'customer.address1' => $lead_data['data']['home_street'],
			'customer.address2' => '',
			'customer.city' => $lead_data['data']['home_city'],
			'customer.state' => $lead_data['data']['home_state'],
			'customer.zip' => $lead_data['data']['home_zip'],
			
			'customer.phone1.area' => $_tmp['ph_area_code'],
			'customer.phone1.prefix' => $_tmp['ph_prefix'],
			'customer.phone1.exchange' => $_tmp['ph_exchange'],
			
			'customer.email' => $lead_data['data']['email_primary'],
			
			'customer.ssn.first' => $lead_data['data']['ssn_part_1'], //$_tmp['ssn_first'],
			'customer.ssn.second' => $lead_data['data']['ssn_part_2'],
			'customer.ssn.third' => $lead_data['data']['ssn_part_3'],
			
			//'customer.person.mothersMaidenName' => $lead_data['data']['name_mothers_maiden'], //@@ToDo fix maidenname[DONE]
			'customer.person.mothersMaidenName' => 'NONE',
			
			'customer.residence.timeAtResidence' => 24,							//@@ToDo int vs string (done, int)
			
			'customer.dob.date' => $_tmp['dob'],
			
			'customer.phone2.area' => $_tmp['ph2_area_code'],
			'customer.phone2.prefix' => $_tmp['ph2_prefix'],
			'customer.phone2.exchange' => $_tmp['ph2_exchange'],
			
			'customer.employment.netSalary' => $lead_data['data']['income_monthly_net'], //as per Joseph Hegedus
			
			'customer.bankAccount.routingNumber' => $lead_data['data']['bank_aba'],
			'customer.bankAccount.accountNumber' => $lead_data['data']['bank_account'],
			
			'customer.driversLicense.number' => $lead_data['data']['state_id_number'],
			
			'customer.driversLicense.state' => $lead_data['data']['state_issued_id'],
			
			'customer.bankAccount.directDeposit' => $_tmp['payrolltype'],
			
			'customer.bankAccount.accountType' => $_tmp['bankaccttype'],
			
			//parse for their possible values: benefits, employment
			'customer.employment.incomeSource' => $_tmp['income_source'],
			
			
			'customer.employment.payDate1' => $_tmp['nextpaydate'],
			'customer.employment.payDate2' => $_tmp['secondpaydate'],
			'customer.employment.employerName' => $lead_data['data']['employer_name'],
			
			//added for new spec:
			'customer.employment.howOftenPaid' => $_tmp['ccrt_paydate']
			
			//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
			
			
		);

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
		preg_match('/(http.*ACCEPTED)/', $data_received, $matches);
		if(preg_match('!<redirectURL>([^<]+)?</redirectURL>!i', $data_received, $match))
		{
			$url = ($match[1]);
			$url = str_replace("&amp;","&",$url);
		}
		
		$content = parent::Generic_Thank_You_Page($url, self::REDIRECT);
		return($content);
		
	}
	
	public function Generate_Result(&$data_received, &$cookies)
	{
		$result = new Vendor_Post_Result();
		
		if (!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		elseif(preg_match('!<responseCode>OK</responseCode>!i', $data_received))
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content($data_received));
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

	/*public function Generate_Result(&$data_received, &$cookies)
	{

		$result = new Vendor_Post_Result();

		if (!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		else
		{
			$tmp = explode("\n", $data_received);
			foreach ($tmp as $val) {
				$val = trim($val);
				if (!empty($val))
					$data[substr($val, 0, strpos($val, '='))] = substr($val, strpos($val, '=')+1);
			}

			if (strcasecmp($data['responseCode'],'OK')==0) {
				$result->Set_Message("Accepted");
				$result->Set_Success(TRUE);
				$result->Set_Thank_You_Content( self::Thank_You_Content(($data['redirectURL'])));
				$result->Set_Vendor_Decision('ACCEPTED');
			} else {
				$result->Set_Message("Rejected");
				$result->Set_Success(FALSE);
				$result->Set_Vendor_Decision('REJECTED');
				$result->Set_Vendor_Reason($data['Reason']);
			}
		}

		return $result;
	}*/

	/**
	 * A PHP magic function.
	 *
	 * @link http://www.php.net/manual/en/language.oop5.magic.php
	 * @return string a string describing this class.
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [ccrt]";
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
