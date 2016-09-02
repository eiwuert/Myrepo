<?php

require_once(BFW_CODE_DIR.'dupe_bloom_singleton.class.php');

/**
 * @desc A concrete implementation class to post to CG (Check Giant)
 */
class Vendor_Post_Impl_CG_NEW extends Abstract_Vendor_Post_Implementation
{
	
	// must be passed as 2nd arg to Generic_Thank_You_Page:
	//		parent::Generic_Thank_You_Page($url, self::REDIRECT);
	const REDIRECT = 2;
	protected $vendor_redirect_url;
	
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
			    'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/CG_NEW',
				//'post_url' =>  'https://wwwdev2.cashnetusa.com/import-partnerweekly.html',
				//'post_url' => 'https://wwwdev.cashnetusa.com/es/import-partnerweeklyes.html',
				//'vendor_redirect_url' => 'https://wwwdev2.cashnetusa.com/confirmation-partnerweekly/',
				//'vendor_redirect_url' => 'https://wwwdev.cashnetusa.com/es/confirmation-partnerweeklyes/',
				'password' => 'password',
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
					//'post_url' => 'https://www.cashnetusa.com/es/import-partnerweeklyes.html',
				),
			'LIVE'    => Array(
				'post_url' => 'https://leads.cashnetusa.com/import-partnerweekly.html', // GForge #3417 [DY]
				'vendor_redirect_url' => 'https://leads.cashnetusa.com/confirmation-partnerweekly/', // GForge #3417 [DY]
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'cg_uk' => array(
				'ALL' => array(
					'post_url' => 'https://leadsdev.quickquid.co.uk/import-partnerweeklyuk.html',
					'id' => 't1',
					'password' => 'password',
					),
				'LIVE'    => Array(
					'post_url' => 'https://leads.quickquid.co.uk/import-partnerweeklyuk.html',),
				),				
			'cg_nd' => array(
				
				'ALL' => array(
					'id' => 't1',
					),
			),
			'cgdd_nd' => array(
				
				'ALL' => array(
					'id' => 't1',
					),
			),
			'cg_nd_t1' => array(
				
				'ALL' => array(
					'id' => 't1',
					),
			),			
			'cg_nd_t2' => array(

				'ALL' => array(
					'id' => 't1',
					),
			),			
			'cg4' => array(
				
				'ALL' => array(
					'id' => 't2',
					),				
			),
			'cg4b' => array(
				
				'ALL' => array(
					'id' => 't3',
					),
			),
			'cg_rm1' => array(
				'ALL' => array(
					'post_url' =>  'https://wwwdev2.cashnetusa.com/import-rml.html',
					//'post_url' => 'https://www.cashnetusa.com/import-rml.html',
					'id' => 't1',
					'password' => 'password', 
					),
				'LIVE'    => Array(
					'post_url' => 'https://www.cashnetusa.com/import-rml.html',
				),
			),		
			'cg_rml2' => array(
				'ALL' => array(
					'post_url' =>  'https://wwwdev2.cashnetusa.com/import-rml.html',
					//'post_url' => 'https://www.cashnetusa.com/import-rml.html',
					'id' => 't2',
					'password' => 'password', 
					),
				'LIVE'    => Array(
					'post_url' => 'https://www.cashnetusa.com/import-rml.html',
				),
			),			
			'cgt4' => array(
				'ALL' => array(
					'id' => 't4',
					),
				'LIVE'    => Array(
				),
			),
			'cgt5' => array(
				'ALL' => array(
					'id' => 't5',
					),
				'LIVE'    => Array(
				),
			),
			'cg_sp1' => array(
				
				'ALL' => array(
					'id' => 't1',
					// 'post_url' => 'https://www2.dev.cashnetusa.com/es/import-partnerweeklyes.html',	
					'password' => 'password',
					'date_format' => 'Y-m-d',
				),
				'RC' => Array(
					'post_url' => 'https://www2.dev.cashnetusa.com/es/import-partnerweeklyes.html',
					'vendor_redirect_url' => 'https://wwwdev2.cashnetusa.com/es/confirmation-partnerweeklyes/leadid.html'
					),
				'LIVE'    => Array(
					// 'post_url' => 'https://www.cashnetusa.com/es/import-partnerweeklyes.html',
					// 'vendor_redirect_url' => 'https://www.cashnetusa.com/es/confirmation-partnerweeklyes/'
				),
			),
			'cg_sp2' => array(
				
				'ALL' => array(
					'id' => 't1',
					// 'post_url' => 'https://www2.dev.cashnetusa.com/es/import-partnerweeklyes.html',	
					'password' => 'password',
					'date_format' => 'Y-m-d',
					),
				'RC' => Array(
					'post_url' => 'https://www2.dev.cashnetusa.com/es/import-partnerweeklyes.html',
					'vendor_redirect_url' => 'https://wwwdev2.cashnetusa.com/es/confirmation-partnerweeklyes/leadid.html'
					),
				'LIVE'    => Array(
					// 'post_url' => 'https://www.cashnetusa.com/es/import-partnerweeklyes.html',
					// 'vendor_redirect_url' => 'https://www.cashnetusa.com/es/confirmation-partnerweeklyes/'
				),
					
			),
			'cg_sp3' => array(
				
				'ALL' => array(
					'id' => 't2',
					// 'post_url' => 'https://www2.dev.cashnetusa.com/es/import-partnerweeklyes.html',	
					'password' => 'password',
					'date_format' => 'Y-m-d',
					),
				'RC' => Array(
					'post_url' => 'https://www2.dev.cashnetusa.com/es/import-partnerweeklyes.html',
					'vendor_redirect_url' => 'https://wwwdev2.cashnetusa.com/es/confirmation-partnerweeklyes/leadid.html'
					),
				'LIVE'    => Array(
					// 'post_url' => 'https://www.cashnetusa.com/es/import-partnerweeklyes.html',
					// 'vendor_redirect_url' => 'https://www.cashnetusa.com/es/confirmation-partnerweeklyes/'
				),
					
			),
			'cg_sp4' => array(
				
				'ALL' => array(
					'id' => 't3',
					// 'post_url' => 'https://www2.dev.cashnetusa.com/es/import-partnerweeklyes.html',	
					'password' => 'password',
					'date_format' => 'Y-m-d',
					),
				'RC' => Array(
					'post_url' => 'https://www2.dev.cashnetusa.com/es/import-partnerweeklyes.html',
					'vendor_redirect_url' => 'https://wwwdev2.cashnetusa.com/es/confirmation-partnerweeklyes/leadid.html'
					),
				'LIVE'    => Array(
					// 'post_url' => 'https://www.cashnetusa.com/es/import-partnerweeklyes.html',
					// 'vendor_redirect_url' => 'https://www.cashnetusa.com/es/confirmation-partnerweeklyes/'
				),
					
			),
			'cg_sp5' => array(
				
				'ALL' => array(
					'id' => 't4',
					// 'post_url' => 'https://www2.dev.cashnetusa.com/es/import-partnerweeklyes.html',	
					'password' => 'password',
					'date_format' => 'Y-m-d',
					),
				'RC' => Array(
					'post_url' => 'https://www2.dev.cashnetusa.com/es/import-partnerweeklyes.html',
					'vendor_redirect_url' => 'https://wwwdev2.cashnetusa.com/es/confirmation-partnerweeklyes/leadid.html'
					),
				'LIVE'    => Array(
					// 'post_url' => 'https://www.cashnetusa.com/es/import-partnerweeklyes.html',
					// 'vendor_redirect_url' => 'https://www.cashnetusa.com/es/confirmation-partnerweeklyes/'
				),
					
			),
			'cg_sp6' => array(
				
				'ALL' => array(
					'id' => 't5',
					// 'post_url' => 'https://www2.dev.cashnetusa.com/es/import-partnerweeklyes.html',	
					'password' => 'password',
					'date_format' => 'Y-m-d',
					),
				'RC' => Array(
					'post_url' => 'https://www2.dev.cashnetusa.com/es/import-partnerweeklyes.html',
					'vendor_redirect_url' => 'https://wwwdev2.cashnetusa.com/es/confirmation-partnerweeklyes/leadid.html'
					),
				'LIVE'    => Array(
					// 'post_url' => 'https://www.cashnetusa.com/es/import-partnerweeklyes.html',
					// 'vendor_redirect_url' => 'https://www.cashnetusa.com/es/confirmation-partnerweeklyes/'
				),
					
			),
			
		);
	
	protected $static_thankyou = TRUE;
	
	
	public function Generate_Fields(&$lead_data, &$params)
	{

		$this->vendor_redirect_url = $params['vendor_redirect_url'];
		$issued_state = ($lead_data['data']['state_issued_id']) ? $lead_data['data']['state_issued_id'] : $lead_data['data']['home_state'];
		
		if( 
			isset($lead_data['data']['paydate_model']) 
			&& isset($lead_data['data']['paydate_model']['income_frequency']) 
			&& $lead_data['data']['paydate_model']['income_frequency'] != "")
		{
			$freq = $lead_data['data']['paydate_model']['income_frequency'];
		}
		elseif(
			isset($lead_data['data']['income_frequency']) 
			&& $lead_data['data']['income_frequency'] != "")
		{
			$freq = $lead_data['data']['income_frequency'];
		}
		elseif(
			isset($lead_data['data']['paydate']) 
			&& isset($lead_data['data']['paydate']['frequency']) &&
			$lead_data['data']['paydate']['frequency'] != "")
		{
			$freq = $lead_data['data']['paydate']['frequency'];
		}
		$fields = array (
			'group_cd' => $params['id'],
			'lead_password' => $params['password'],
			'lead_id' => $lead_data['application_id'],
			'first_name' => $lead_data['data']['name_first'],
			'middle_name' => $lead_data['data']['name_middle'],
			'last_name' => $lead_data['data']['name_last'],
			'home_phone_day' => $lead_data['data']['phone_home'],
			'work_phone' => $lead_data['data']['phone_work'],
			'home_phone_mobile' => $lead_data['data']['phone_cell'],
			'email' => $lead_data['data']['email_primary'],
			'dob' => $lead_data['data']['dob'],
			'ssn' => $lead_data['data']['social_security_number'],
			'driver_license' => $lead_data['data']['state_id_number'],
			'driver_license_state' => $issued_state,
			'best_call_time' => $lead_data['data']['best_call_time'],
			'relative_name' => $lead_data['data']['ref_01_name_full'],
			'relative_phone_home' => $lead_data['data']['ref_01_phone_home'],
			'relative_relationship' => $lead_data['data']['ref_01_relationship'],
			'relative2_name' => $lead_data['data']['ref_02_name_full'],
			'relative2_relationship' => $lead_data['data']['ref_02_relationship'],
			'relative2_phone_home' => $lead_data['data']['ref_02_phone_home'],
			'home_address' => $lead_data['data']['home_street'] .' '. $lead_data['data']['home_unit'],
			'home_city' => $lead_data['data']['home_city'],
			'home_state' => $lead_data['data']['home_state'],
			'home_zip' => $lead_data['data']['home_zip'],
			'home_type' => $lead_data['data']['residence_type'],
			'bank_name' => $lead_data['data']['bank_name'],
			'bank_account_number' => $lead_data['data']['bank_account'],
			'bank_routing_number' => $lead_data['data']['bank_aba'],
			'income_payment_type' => ($lead_data['data']['income_direct_deposit'] == 'TRUE') ? 'direct_deposit' : 'paper_check',
			'bank_account_type' => $lead_data['data']['bank_account_type'],
			'registration_ip' => $lead_data['data']['client_ip_address'],
			'company_name' => $lead_data['data']['employer_name'],
			'employer_phone' => $lead_data['data']['phone_work'], 
			'income_type' => $lead_data['data']['income_type'],
			'income_net_monthly' => $lead_data['data']['income_monthly_net'],
			'income_payment_period' => $freq,
			'income_next_date1' => date("Y-m-d", strtotime($lead_data['data']['paydates'][0])),
			'income_next_date2' => date("Y-m-d", strtotime($lead_data['data']['paydates'][1])),
			'timeout' => '13',
			'lead_source' => $_SESSION["config"]->promo_id, // added Mantis #8075 [DY]
			'active_military' => $lead_data['data']['military'], //added Mantis #12016 [MJ]
			'country_cd' => 'US', // GForge #6759 [DY]
		);
		
		if(strcasecmp($this->property_short,'cg_nd_t1') === 0)// GForge #3989 [MJ]
		{
			$fields['lead_source'] = 't1'.$fields['lead_source'];
		}
		
		return array_map(array('Vendor_Post_Impl_CG_NEW','Clean_Value'), $fields);
	}
	
	public static function Clean_Value($value)
	{
		$value = str_replace("|","",$value);
		return   str_replace("'","",$value);
	}
	

					/**
					 * [#3395] BBx - Check Giant - Dup Check [TF]
					 * Posts leads to CG Vendors
					 * Overrides the Post() function in abstract_vendor_post_implementation
					 * transfers control to Generate_Fields() and Generate_Result()
					 *
					 * @see /include/code/dupe_bloom_singleton.class.php
					 */
					public function Post()
					{
						$this->Merge_Params();
						
						$lead_data = $this->Get_Lead_Data();
						$this->movePayDate($lead_data);
						$fields = $this->Generate_Fields($lead_data, $this->params);

						$result = new Vendor_Post_Result();

						$fred=Dupe_Bloom_Singleton::Get_Instance();
						if($fred->is_dupe($fields['email'],$fields['ssn']))
						{
							$result->Set_Message("Rejected");
							$result->Set_Success(FALSE);
							$result->Set_Vendor_Decision('REJECTED');
							$reason="Failed OLP/CG dupe check";
							$result->Set_Vendor_Reason($reason);
							$result->Set_Data_Sent(serialize($fields));
							//following response message must stay for CG-- usurped as a flag 
							//used in olp.php Post_To_Winner
							$result->Set_Data_Received("failed_cg_dupes");
						}
						else
						{
							//passed the bloom filter check
							$result = $this->HTTP_Post_Process($fields,FALSE);
						}
							
						return $result;
					}


	public function Generate_Result(&$data_received, &$cookies)
	{

		$result = new Vendor_Post_Result();

		if (!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		elseif	 (strpos($data_received, 'success') !== FALSE)
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
			
			$m = array();
			preg_match('/reject: (.*)/i', $data_received, $m);
			$reason = substr($m[1],0,255);
			$result->Set_Vendor_Reason($reason);
		
			if(trim(strtolower($reason)) == 'price reject'){
				$_SESSION['bypass_withheld_targets'] = TRUE;
			}
			else {
				$_SESSION['bypass_withheld_targets'] = FALSE;
			}
					
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
		return "Vendor Post Implementation [CG_NEW]";
	}
	

	
	public function Thank_You_Content(&$data_received)
	{
		$url = $this->vendor_redirect_url . $_SESSION['application_id'] . '.html';
		
		$content = parent::Generic_Thank_You_Page($url, self::REDIRECT);	
		return($content);		
		
	}

	
}
