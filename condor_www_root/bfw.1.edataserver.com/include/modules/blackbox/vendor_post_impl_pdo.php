<?php
/**
 * @desc A concrete implementation class for posting to pdo (Payday OK)
 */
class Vendor_Post_Impl_PDO extends Abstract_Vendor_Post_Implementation
{
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
		    	'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/PDO2',
				'id' => 'C16397x005',
				 'headers' => array( // Added To Header per Client Server change. [AuMa]
				      'Content-Type: application/x-www-form-urlencoded',
				  ),
				'testApp'  => TRUE,
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'testApp'  => FALSE,
				'post_url' => 'https://www.paydayselect.com/modules/cashadvance/leadpost.asp',
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'pdo1'    => Array(
				'ALL'      => Array(
					'id' => 'C16397x015',
					),
				),
			'pdo_sf'    => Array(
				'ALL'      => Array(
				'id' => 'C16397x041',
					),
				),
			'pdo4'    => Array(
				'ALL'      => Array(
				'id' => 'C16397x055',
					),
				),
			// added Task # 11552 [AuMa]
			'pdo_tc'    => Array(
				'ALL'      => Array(
				'id' => 'tc017',
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.thinkcash.com/modules/cashadvance/leadpost.aspx',
					),
				),
			'pdo6'    => Array(
				'ALL'      => Array(
				'id' => 'C16397x041',
					),
				),	
		);
	
	protected $static_thankyou = FALSE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		$map_incomesource = array (
			'EMPLOYMENT' => 'Employment',
			'BENEFITS' => 'Benefits',
		);

		$map_payperiod = array(
			'WEEKLY' => 'Weekly',
			'BI_WEEKLY' => 'Bi-Weekly',
			'TWICE_MONTHLY' => 'Semi-Monthly',
			'MONTHLY' => 'Monthly',
		);

		$paycycle = '';
		if (isset($lead_data['data']['paydate']['frequency']))
		{
			$paycycle = $map_payperiod[$lead_data['data']['paydate']['frequency']];
		}
		if (!$paycycle && isset($lead_data['data']['income_frequency']))
		{
			$paycycle = $map_payperiod[$lead_data['data']['income_frequency']];
		}
		if (!$paycycle && isset($lead_data['data']['paydate_model']['income_frequency']))
		{
			$paycycle = $map_payperiod[$lead_data['data']['paydate_model']['income_frequency']];
		}
		
		$promo_id =  $_SESSION["config"]->promo_id; // added Task #11553 [AuMa]
		$fields = array (
			'LeadGenID' => $params['id'],
			'sub' => $promo_id, // added Task #11553 [AuMa]
			'firstName' => $lead_data['data']['name_first'],
			'lastName' => $lead_data['data']['name_last'],
			'email' => $lead_data['data']['email_primary'],
			'ssn' => $lead_data['data']['social_security_number'],
			'dob'	=>  $lead_data['data']['date_dob_m'].'/'.$lead_data['data']['date_dob_d'].'/'.$lead_data['data']['date_dob_y'],
			'address' => $lead_data['data']['home_street'].($lead_data['data']['home_unit'] ? ' '.$lead_data['data']['home_unit'] : ''),
			'city'	=> $lead_data['data']['home_city'],
			'state' => $lead_data['data']['home_state'],
			'zip' => $lead_data['data']['home_zip'],
			'homephone' => $lead_data['data']['phone_home'],
			'incomeType' => $map_incomesource[strtoupper($lead_data['data']['income_type'])],
			'employer' => $lead_data['data']['employer_name'],
			'workPhone' => substr($lead_data['data']['phone_work'],0,3).'-'.substr($lead_data['data']['phone_work'],3,3).'-'.substr($lead_data['data']['phone_work'],6,4),
			'firstPayDay' => date("m/d/Y", strtotime($lead_data['data']['paydates'][0])),
			'secondPayDay' => date("m/d/Y", strtotime($lead_data['data']['paydates'][1])),
			'monthlyIncome' => $lead_data['data']['income_monthly_net'],
			'payCycle' => $paycycle,
			'bankABA' => $lead_data['data']['bank_aba'],
			'accountNumber' => $lead_data['data']['bank_account'],
			'directDeposit' => (strtolower($lead_data['data']['income_direct_deposit']) == 'true' ? 'Y' : 'N'),
			'testApp' => (($params['testApp']) ? 1 : 0),
		);
	
		$fields['military'] = (strcasecmp($lead_data['data']['military'], 'TRUE') === 0) ? '1' : '0'; // Mantis #12049 [DY]
		
		return $fields;
	}

	public function Generate_Result(&$data_received, &$cookies)
	{
		
		//Changed to reflected normal cookies vs double header that was previously sent. [MJ]
		$result = new Vendor_Post_Result();
			
		if (empty($cookies['Response']))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		elseif(strcasecmp($cookies['Response'],'success') == 0)
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content($cookies) );
			$result->Set_Vendor_Decision('ACCEPTED');

			
		}
		else
		{
			preg_match('/rejectDetails=(.*)\; path/i', $cookies['Response'], $m);
			$reason = urldecode($m[1]);
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
			$result->Set_Vendor_Reason($reason);


		}


		return $result;
	}


	/**
	 * @desc HTTP Post Processing
	 *	Post to Impl post_url with Fields
	 */
	public function HTTP_Post_Process($fields, $qualify = FALSE)
	{
		$http_client = $this->Get_Http_Client();

		//Set the headers if we have any
		if(isset($this->params['headers']))
		{
			$http_client->Set_Headers($this->params['headers']);
		}

		// vendors may want to use a different url to
		// handle the verifify xml post
		$post_url = $qualify && isset($this->params['qualify_post_url'])
					?
				$this->params['qualify_post_url']
					:
				$this->params['post_url'];

		if ($post_url)
		{
			$post_or_get = $this->Get_Post_Type();
			if ($post_or_get == Http_Client::HTTP_GET)
			{
				$data_received = $http_client->Http_Get($post_url, $fields);
			}
			else // Must be Http_Client::HTTP_POST
			{
				$data_received = $http_client->Http_Post($post_url, $fields);
			}
		}
			
		$cookies = $http_client->Get_Cookies();
		
		foreach($cookies as $key  => $value)
		{
			$cookies_decoded[$key] = urldecode($value);
		}
		
		$data_received = print_r($cookies_decoded,true);
		$result = ($qualify) ? $this->Verify_Result($data_received, $cookies)
							 : $this->Generate_Result($data_received, $cookies);

		// If we have both an original winner, and a lender redirected target, we need to do a little extra work [LR]
		// We'll post the data sent, and data received for both targets
		if (is_array($result))
		{
			foreach ($result as $r)
			{
				$r->Set_Data_Sent(serialize($fields));
				$r->Set_Data_Received($data_received);
			}
		}
		else
		{
			$result->Set_Data_Sent(serialize($fields));
			$result->Set_Data_Received($data_received);
		}
		if (!$this->params['post_url'])
		{
			$result->Set_Message("No post_url found in params");
            if ($this->mode == 'LOCAL' || $this->mode == 'RC')
            {
                $result->Set_Message("Accepted");
                $result->Set_Data_Received("No post_url for Local/RC.  Forcing Accept");
                $result->Set_Thank_You_Content(self::Generic_Thank_You_Page(""));
                $result->Set_Success(TRUE);
            }
		}
		if ($http_client->timeout_exceeded)
		{
			$this->timeout_exceeded = TRUE;
		}

		return $result;

	}	
	
	
	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [PDO]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		
		$content = NULL;
		
		if (isset($data_received['guid']) && isset($data_received['gcid']))
		{
			
			// Check to see if redirection url is sent back and if so, set $temp_url to it.
			if (isset($data_received['message'])){
			   	$tmp_url = urldecode($data_received['message']);
			}	
			
			$key = array_search('guid', $data_received);
			if ($key!==FALSE) $guid = $data_received[$key];
			
			$key = array_search('gcid', $data_received);
			if ($key!==FALSE) $gcid = $data_received[$key];
			
			if (!$tmp_url)
			{
				$tmp_url = "https://www.paydayselect.com/modules/cashadvance/leadApplication.asp?guid={$guid}&GCID={$gcid}";
			}
			$content = parent::Generic_Thank_You_Page($tmp_url);
			
		}
		
		if (!$content) $content = parent::Generic_Thank_You_Page("");
		return($content);
		
	}
}
?>
