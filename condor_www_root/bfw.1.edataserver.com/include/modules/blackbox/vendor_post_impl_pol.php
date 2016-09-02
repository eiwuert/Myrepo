<?php

/**
 * @desc A concrete implementation class for posting to efm, bmg172
 */
class Vendor_Post_Impl_POL extends Abstract_Vendor_Post_Implementation
{
	
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
				'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/POL',
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				'post_url' => '',
				),
			'RC'      => Array(
				'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/POL', // dummy url
				),
			'LIVE'    => Array(
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'pol'    => Array(
				'ALL'      => Array(
					//the testing server please use this but you might need to contact
					// Marshall first to make sure the server is running

//					'post_url' => 'http://75.84.216.82/paydayonline/sellingsource.aspx'
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => "http://63.230.217.101/paydayonline/sellingsource.aspx"
					),
				),
		);
	
	protected $static_thankyou = FALSE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		$url = $params['post_url'];

		$checking = ($lead_data['data']['bank_account_type'] == 'CHECKING') ? 'TRUE' : 'FALSE';

		foreach($lead_data['data']['paydates'] as $key =>$parsed_date) {
			$paydates[$key]['y'] = substr($parsed_date,0,4);
			$paydates[$key]['m'] = substr($parsed_date,5,2);
			$paydates[$key]['d'] = substr($parsed_date,8,2);
		}
		$bankname = ($lead_data['data']['aba_call_result']['valid'] == 1) ? $lead_data['data']['aba_call_result']['bank_name'] : $lead_data['data']['bank_name'] ;

		$offers = ($lead_data['data']['offers'] != '' && $lead_data['data']['offers'] === TRUE) ? 'TRUE' : 'FALSE';
		$offers = ($offers != TRUE) ? $lead_data['data']['mh_offer'] : $offers;
		$issued_state = ($lead_data['data']['state_issued_id']) ? $lead_data['data']['state_issued_id'] : $lead_data['data']['home_state'];
		$best_call_time = ($lead_data['data']['best_call_time'] != '') ? $lead_data['data']['best_call_time'] : 'AFTERNOON';
		$employer_length = ($lead_data['data']['employer_length'] == 'TRUE') ? 3 : 1;

		$fields = array (
			'employer_length' => $employer_length,
			'citizen' => 'TRUE',
			'client_url_root' => $lead_data['data']['client_url_root'],
			'offers' => $offers,
			'client_ip_address' => $lead_data['data']['client_ip_address'],
			'best_call_time' => $best_call_time,
			'checking_account' => $checking,
	//		'loanamt' => $params['loanamt'],
			'name_first' => $lead_data['data']['name_first'],
			'email_primary' => $lead_data['data']['email_primary'],
			'income_frequency' => $lead_data['data']['paydate_model']['income_frequency'],
			'name_last' => $lead_data['data']['name_last'],
			'ssn_part_1' => $lead_data['data']['ssn_part_1'],
			'ssn_part_2' => $lead_data['data']['ssn_part_2'],
			'ssn_part_3' => $lead_data['data']['ssn_part_3'],
			'state_id_number' => $lead_data['data']['state_id_number'],
			'state_issued_id' => $issued_state,
			'date_dob_d' => $lead_data['data']['date_dob_d'],
			'date_dob_m' => $lead_data['data']['date_dob_m'],
			'date_dob_y' => $lead_data['data']['date_dob_y'],
			//'dob' => $lead_data['data']['dob'],
			'email_primary' => $lead_data['data']['email_primary'],
			'home_street' => $lead_data['data']['home_street'],
			'home_unit' => $lead_data['data']['home_unit'],
			'home_city' => $lead_data['data']['home_city'],
			'home_state' => $lead_data['data']['home_state'],
			'home_zip' => $lead_data['data']['home_zip'],
			'phone_home' => $lead_data['data']['phone_home'],
			'phone_work' => $lead_data['data']['phone_work'],
			'ext_work' => $lead_data['data']['ext_work'],
			'phone_cell' => $lead_data['data']['phone_cell'],
			'income_monthly_net' => $lead_data['data']['income_monthly_net'],
			'income_type' => $lead_data['data']['income_type'],
			'employer_name' => $lead_data['data']['employer_name'],
			'income_frequency' => $lead_data['data']['paydate_model']['income_frequency'],
			'income_date1_d' => $paydates[0]['d'],
			'income_date1_m' => $paydates[0]['m'],
			'income_date1_y' => $paydates[0]['y'],
			'income_date2_d' => $paydates[1]['d'],
			'income_date2_m' => $paydates[1]['m'],
			'income_date2_y' => $paydates[1]['y'],
			'income_direct_deposit' => $lead_data['data']['income_direct_deposit'],
			'bank_account_type' => $lead_data['data']['bank_account_type'],
			'bank_name' => $bankname,
			'bank_account' => $lead_data['data']['bank_account'],
			'bank_aba' => $lead_data['data']['bank_aba'],
			'ref_01_name_full' => $lead_data['data']['ref_01_name_full'],
			'ref_01_phone_home' => $lead_data['data']['ref_01_phone_home'],
			'ref_01_relationship' => $lead_data['data']['ref_01_relationship'],
			'ref_02_name_full' => $lead_data['data']['ref_02_name_full'],
			'ref_02_phone_home' => $lead_data['data']['ref_02_phone_home'],
			'ref_02_relationship' => $lead_data['data']['ref_02_relationship'],			
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
		elseif(preg_match('!<response>Accept </response>!i', $data_received))
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content($data_received) );
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
		return "Vendor Post Implementation [POL]";
	}
	
	public static function Thank_You_Content(&$data_received)
	{
		$matches = array();
		preg_match('/(http.*ACCEPTED)/', $data_received, $matches);
		if(preg_match('!<redirect-url>([^<]+)?</redirect-url>!i', $data_received, $match))
		{
			$url = html_entity_decode($match[1]);
		}

		$content = parent::Generic_Thank_You_Page($url, self::REDIRECT);
		return($content);
		
	}
	
}
