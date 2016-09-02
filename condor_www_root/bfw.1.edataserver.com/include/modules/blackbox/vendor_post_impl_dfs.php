<?php

/**
 * @desc A concrete implementation class for posting to AME
 */
class Vendor_Post_Impl_DFS extends Abstract_Vendor_Post_Implementation
{
	
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
			  'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/DFS',
			  //'post_url' => 'https://secure.cashcentral.com/cgi-bin/affiliates/member_lead',
				'referring_site' => 'partnerweekly',
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				//'post_url' => 'https://training.secure.cashcentral.com/cgi-bin/affiliates/member_lead',
				//'referring_site' => '911paydayadvance.com',
				),
			'RC'      => Array(
				//'post_url' => 'https://training.secure.cashcentral.com/cgi-bin/affiliates/member_lead',
				'referring_site' => '911paydayadvance.com',
				),
			'LIVE'    => Array(
				'post_url' => 'https://secure.cashcentral.com/cgi-bin/affiliates/member_lead',
				),	
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'cc2'    => Array(
				'ALL'      => Array(
				'referring_site' => 'partnerweekly2',
					),
				),
			'cc3'    => Array(
				'ALL'      => Array(
				'referring_site' => 'partnerweekly3', // GForge #6042 [DY]
					),
				),		
		);
	
	protected $static_thankyou = FALSE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		        //Paydate Freq
		if(	isset($lead_data['data']['paydate_model']) && 
			isset($lead_data['data']['paydate_model']['income_frequency']) &&
			$lead_data['data']['paydate_model']['income_frequency'] != "")
		{
			$freq = $lead_data['data']['paydate_model']['income_frequency'];
		}
		elseif(	isset($lead_data['data']['income_frequency']) && 
			$lead_data['data']['income_frequency'] != "")
		{
			$freq = $lead_data['data']['income_frequency'];
		}
		elseif(	isset($lead_data['data']['paydate']) && 
			isset($lead_data['data']['paydate']['frequency']) &&
			$lead_data['data']['paydate']['frequency'] != "")
		{	
			$freq = $lead_data['data']['paydate']['frequency'];
		}
		
		
		switch ($freq)
		{
			case "WEEKLY":
				$freq = "weekly";
				break;
			case "BI_WEEKLY":
				$freq = "bi-weekly";
				break;		
			case "TWICE_MONTHLY":
				$freq = "twice_a_month";
				break;
			case "MONTHLY":
				$freq = "mconthly";
				break;
		}
		$military = ($lead_data['data']['military'] == 'TRUE') ? 'Y' : 'N' ;
		
		$fields = array(
			'affiliate_id' => $lead_data['data']['promo_id'],
			'fname' => $lead_data['data']['name_first'],
			'lname' => $lead_data['data']['name_last'],
			'email' => $lead_data['data']['email_primary'],
			'home_phone' => $lead_data['data']['phone_home'],
			'address' => $lead_data['data']['home_street'] .' '. $lead_data['data']['home_unit'],
			'city' => $lead_data['data']['home_city'],
			'state' => $lead_data['data']['home_state'],
			'zip' => $lead_data['data']['home_zip'],
			'employer' => $lead_data['data']['employer_name'],
			'work_phone' => $lead_data['data']['phone_work'],
			//'yearsemployed' not available
			//'monthsemployed' not available
			'salary' => $lead_data['data']['income_monthly_net'],
			//'pay_type' => $lead_data['data']['income_type'],
			'pay_type' => $freq,
			'pay_date_1' => date("m/d/Y", strtotime($lead_data['data']['paydates'][0])),
			'pay_date_2' => date("m/d/Y", strtotime($lead_data['data']['paydates'][1])),
			'bank_name' => $lead_data['data']['bank_name'],
			//'bank_phone' not available
			'bank_account_number' => $lead_data['data']['bank_account'],
			'bank_routing_number' => $lead_data['data']['bank_aba'],
			'ssn' => $lead_data['data']['social_security_number'],
			'birthday' => $lead_data['data']['dob'],
			'ref1' => $lead_data['data']['ref_01_name_full'],
			'ref1_relationship' => $lead_data['data']['ref_01_relationship'],
			'ref1_phone' => $lead_data['data']['ref_01_phone_home'],
			'ref2' => $lead_data['data']['ref_02_name_full'],
			'ref2_relationship' => $lead_data['data']['ref_02_relationship'],
			'ref2_phone' => $lead_data['data']['ref_02_phone_home'],
			'referring_site' => $params['referring_site'],
			'military' => $military,
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
		// changed to '/>accept</' to make sure the word accept is surrounded by tags.
		elseif ( preg_match( '/>accept</', $data_received ) )
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
			if(preg_match('/<body>\n(.+)<br>/i', $data_received, $m))
			{
				$result->Set_Vendor_Reason(substr($m[1],0,255));
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
		return "Vendor Post Implementation [DFS]";
	}
	
	public static function Thank_You_Content(&$data_received)
	{

		preg_match("/https:\/\/([0-9\-]*)(.*)/i", $data_received, $matches);
		$url = trim($matches[0]);
		$content = parent::Generic_Thank_You_Page($url, self::REDIRECT);
		
		return($content);
	}
}
