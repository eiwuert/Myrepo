<?php

/**
 * @desc A concrete implementation class for posting to sun and sun2 (Sunshine Advance)
 */
class Vendor_Post_Impl_SUN extends Abstract_Vendor_Post_Implementation
{
	
	// must be passed as 2nd arg to Generic_Thank_You_Page:
	//		parent::Generic_Thank_You_Page($url, self::REDIRECT);
	const REDIRECT = 2;
	
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
			    'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/SUN',
				//'post_url' => 'http://test.911paydayadvance.com/forms/rpost.php',
				'kbid'     => '1002',
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'sun'    => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'kbid' => '1344',
					'post_url' => 'https://secure.paydaylead4u.com/BusinessLayer/Partners/rpost.aspx',
					),
				),
			'sun2'    => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'kbid' => '1342',
					'post_url' => 'https://secure.paydaylead4u.com/BusinessLayer/Partners/rpost.aspx',
					),
				),
			'sun3'    => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'kbid' => '1345',
					'post_url' => 'https://secure.paydaylead4u.com/BusinessLayer/Partners/rpost.aspx',
					),
				),
		);
	
	protected $static_thankyou = FALSE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		$map_incomesource = array (
			'EMPLOYMENT' => 'Job',
			'BENEFITS' => 'Benefits',
		);

		$issued_state = ($lead_data['data']['state_issued_id']) ? $lead_data['data']['state_issued_id'] : $lead_data['data']['home_state'];
		
		$fields = array (
				'kbid' => $params['kbid'], // Selling Source's Unique ID provided by Sunshine
				'activechecking' => (strtolower($lead_data['data']['checking_account']) == 'true' ? 'yes' : 'no'),
				'firstname' => $lead_data['data']['name_first'],
				'lastname' => $lead_data['data']['name_last'],
				'address' => $lead_data['data']['home_street'].($lead_data['data']['home_unit'] ? ' '.$lead_data['data']['home_unit'] : ''),
				'city'  => $lead_data['data']['home_city'],
				'state' => $lead_data['data']['home_state'],
				'zip' => $lead_data['data']['home_zip'],
				'email' => $lead_data['data']['email_primary'],
				'otheroffers' => $lead_data['data']['offers'] == "TRUE" ? 'yes' : 'no',
				'employerPhoneNumber' => substr($lead_data['data']['phone_work'],0,3).'-'.substr($lead_data['data']['phone_work'],3,3).'-'.substr($lead_data['data']['phone_work'],6,4),
				'bankabaRouting' => $lead_data['data']['bank_aba'],
				'bankaccNumber' => $lead_data['data']['bank_account'],
				'bankName' => $lead_data['data']['bank_name'],
				'checkDeposit' => (strtolower($lead_data['data']['income_direct_deposit']) == 'true' ? 'Deposit' : 'Check'),
				'companyname' => $lead_data['data']['employer_name'],
				'currentlyemployed' => (strtolower($lead_data['data']['employer_length']) == 'true' ? 'yes' : 'no'),
				'dob_day' => $lead_data['data']['date_dob_d'],
				'dob_month' => $lead_data['data']['date_dob_m'],
				'dob_year' => $lead_data['data']['date_dob_y'],
				'homephoneNumber' => $lead_data['data']['phone_home'],
				'license' => $lead_data['data']['state_id_number'],
				'licensestate' => $issued_state,
				'mainIncome' => $map_incomesource[$lead_data['data']['income_type']],
				'payPeriod' => isset($lead_data['data']['paydate']['frequency']) ? $lead_data['data']['paydate']['frequency'] : $lead_data['data']['paydate_model']['income_frequency'],
				'reference_name1' => $lead_data['data']['ref_01_name_full'],
				'reference_name2' => $lead_data['data']['ref_02_name_full'],
				'reference_phone1' => $lead_data['data']['ref_01_phone_home'],
				'reference_phone2' => $lead_data['data']['ref_02_phone_home'],
				'reference_relationship1' => $lead_data['data']['ref_01_relationship'],
				'reference_relationship2' => $lead_data['data']['ref_02_relationship'],
				'ssn' => $lead_data['data']['social_security_number'],
				'takehomepay' => $lead_data['data']['income_monthly_net'],
				'uscitizen' => 'yes',
				'paydate1_day' => date("d", strtotime($lead_data['data']['paydates'][0])),
				'paydate1_month' => date("m", strtotime($lead_data['data']['paydates'][0])),
				'paydate1_year' => date("Y", strtotime($lead_data['data']['paydates'][0])),
				'paydate2_day' => date("d", strtotime($lead_data['data']['paydates'][1])),
				'paydate2_month' => date("m", strtotime($lead_data['data']['paydates'][1])),
				'paydate2_year' => date("Y", strtotime($lead_data['data']['paydates'][1])),

				// Pretended values:
				'rentown' => 'Own',
				'months_at_address' => '11',
				'years_at_address' => '1',
				'companyaddress1' => 'x',
				'companycity' => $lead_data['data']['home_city'],
				'companystate' => $lead_data['data']['home_state'],
				'companyzip' => $lead_data['data']['home_zip'],
				'jobtitle' => 'job title',
				'shifthours' => '40',
				'supervisorname' => 'supervisor name',
				'supervisorphone' => '123-123-1234',
				'datehired_day' => '1',
				'datehired_month' => '1',
				'datehired_year' => '2004',
				
				// Turn off URL forwarding:
				'followredirect' => 'false',
				
				// New fields
				'HTTP_REFERER' => $lead_data['data']['client_url_root'],
				'REMOTE_ADDR' => $lead_data['data']['client_ip_address']

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
		elseif (preg_match( '/Location:/i', $data_received))
		{
			$content = self::Thank_You_Content($data_received);
			
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content($content);
			$result->Set_Vendor_Decision('ACCEPTED');
		}
		else
		{
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
			
			$m = array();
			preg_match('/-->(.*)<\/body>/i', $data_received, $m);
			$reason = substr($m[1],0,255);
			$result->Set_Vendor_Reason($reason);
		}
		
		return $result;
	}

	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [sun,sun2,sun3]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		
		$content = NULL;
		
		if( preg_match( '/Location:\s?(\S+)/i', $data_received, $m ) )
		{
			$url = $m[1];
		}
		
		$content = parent::Generic_Thank_You_Page( $url, self::REDIRECT );

		return $content;
		
	}
	
}
