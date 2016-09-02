<?php

/**
 * @desc A concrete implementation class for posting to ct4u (Cash2Day4U.com)
 */
class Vendor_Post_Impl_CT4U extends Abstract_Vendor_Post_Implementation
{
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
			   'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/CT4U',
			//	'post_url' => 'http://www.cash2day4u.com/leadpost.php',
				'lead_source'     => 'test',
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'https://www.cash2day4u.com/leadpost.php',
				'lead_source' => 'SellingSource',
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'ct4u'    => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
				),
			'ct4u_cr'	=> array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'lead_source' => 'ss_short'
					),
				),
			'ct4u2'	=> array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'lead_source' => 'SellingSource_2'
					),
				),
		);
	
	protected $static_thankyou = TRUE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		$url = $params['post_url'];
		
		//  convert income frequency to requested format
		switch($lead_data['data']['paydate']['frequency'])
		{
			case 'WEEKLY':
				$income_frequency = 'Weekly';
				break;

			case 'BIWEEKLY':
				$income_frequency = 'Bi-Weekly';
				break;

			case 'TWICE_MONTHLY':
				$income_frequency = 'Semi-Monthly';
				break;

			case 'MONTHLY':
				$income_frequency = 'Monthly';
				break;

		}

		$issued_state = ($lead_data['data']['state_issued_id']) ? $lead_data['data']['state_issued_id'] : $lead_data['data']['home_state'];

        //reformat date
        $dob = $lead_data['data']['date_dob_y'] . "/" . $lead_data['data']['date_dob_m'] . "/" .
               $lead_data['data']['date_dob_d'];

		$fields = array (
				"Source" => $params['lead_source'],
				"FirstName" => $lead_data['data']['name_first'],
				"Initial" => $lead_data['data']['name_middle'],
				"LastName" => $lead_data['data']['name_last'],
				"Email" => $lead_data['data']['email_primary'],
				"HomePhone" => substr($lead_data['data']['phone_home'], 0, 3).'-'.substr($lead_data['data']['phone_home'], 3, 3).'-'.substr($lead_data['data']['phone_home'], 6, 4),
				"WorkPhone" => substr($lead_data['data']['phone_work'], 0, 3).'-'.substr($lead_data['data']['phone_work'], 3, 3).'-'.substr($lead_data['data']['phone_work'], 6, 4),
				"CellPhone" => substr($lead_data['data']['phone_cell'], 0, 3).'-'.substr($lead_data['data']['phone_cell'], 3, 3).'-'.substr($lead_data['data']['phone_cell'], 6, 4),
				"SSN" => $lead_data['data']['ssn_part_1'].'-'.$lead_data['data']['ssn_part_2'].'-'.$lead_data['data']['ssn_part_3'],
				"DOB" =>  $dob,
				"Addr" => $lead_data['data']['home_street'],
				"City" => $lead_data['data']['home_city'],
				"State" => $lead_data['data']['home_state'],
				"Zip" => $lead_data['data']['home_zip'],
				"License" => $lead_data['data']['state_id_number'],
				"LicenceState" => $issued_state,
				"Employer" => $lead_data['data']['employer_name'],
				"EmploymentStatus" => (strtolower($lead_data['data']['income_type']) == 'employment') ? 'Employed' : 'Benefits',
				"DirectDeposit" => ($lead_data['data']['income_direct_deposit'] == 'TRUE') ? 'Yes' : 'No',
				"PayPeriod" => $income_frequency,
				"MonthlyIncome" => $lead_data['data']['income_monthly_net'],
				"NextPayDate1" => is_array($lead_data['data']['paydates']) ?
					date("Y/m/d", strtotime(reset($lead_data['data']['paydates']))) :
					'',
				"NextPayDate2" => is_array($lead_data['data']['paydates']) ?
					date("Y/m/d", strtotime(next($lead_data['data']['paydates']))) :
					'',
				"BankName" => $lead_data['data']['bank_name'],
				"RoutingNumber" => $lead_data['data']['bank_aba'],
				"BankAccountNumber" => $lead_data['data']['bank_account'],
				"BankAccountType" => $lead_data['data']['bank_account_type'],
				"Name1" => $lead_data['data']['ref_01_name_full'],
				"Phone1" => $lead_data['data']['ref_01_phone_home'],
				"Relationship1" => $lead_data['data']['ref_01_relationship'],
				"Name2" => $lead_data['data']['ref_02_name_full'],
				"Phone2" => $lead_data['data']['ref_02_phone_home'],
				"Relationship2" => $lead_data['data']['ref_01_relationship']
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
		elseif (strpos($data_received, 'OK') !== FALSE)
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content($data_received) );
			$result->Set_Vendor_Decision('ACCEPTED');
			if(!$this->Is_SOAP_Type())// added for Mantis #11073 [AuMa]
			{
				$result->Set_Next_Page( 'bb_vs_thanks' );	
			}
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
		return "Vendor Post Implementation [CT4U]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		$content = 'Your information has been passed to www.cash2day4u.com and you will receive
					an email in the next few minutes providing our contact details, we will also
					contact you at the phone numbers provided. Should you not hear from us
					within the next hour then please email us at 
					<a href="mailto:info@cash2day4u.com">info@cash2day4u.com</a>';
					
			$_SESSION['bb_vs_thanks'] = $content;// added for Mantis #11073 [AuMa]
		if(!$this->Is_SOAP_Type())
		{
			return $content;
		}
		else
		{
			switch(BFW_MODE)
			{
				case 'LIVE':
					$url = 'https://easycashcrew.com';
					break;
				case 'RC':
					$url = 'http://rc.easycashcrew.com';
					break;
				case 'LOCAL':
					$url = 'http://pcl.3.easycashcrew.com.ds70.tss';
			}
			
			//$_SESSION['config']->bb_static_thanks = $content;
			//$_SESSION['bb_vs_thanks'] = $content;
			return parent::Generic_Thank_You_Page($url . '/?page=bb_vs_thanks');// added for Mantis #11073 [AuMa]
			//return parent::Generic_Thank_You_Page($url . '/?page=bb_static_thanks');
		}
	}
	
}
