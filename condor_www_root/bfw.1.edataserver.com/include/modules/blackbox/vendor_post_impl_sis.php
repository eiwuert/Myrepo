<?php

/**
 * @desc A concrete implementation class for posting to Statesville Investment Services
 */
class Vendor_Post_Impl_SIS extends Abstract_Vendor_Post_Implementation
{
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
				'test' => TRUE,
				'recipients' => array(
					array(
						'email_primary_name' => 'Norbinn Rodrigo (test)',
						'email_primary' => 'norbinn.rodrigo@sellingsource.com'
						),
					),						
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'test' => FALSE,
				'recipients' => array(
					array(
						'email_primary_name' => 'Jake Ruzicka',
						'email_primary' => 'jwruzicka@investmentserv.com'
						),
					array(
						'email_primary_name' => 'Norbinn Rodrigo (live)',
						'email_primary' => 'norbinn.rodrigo@sellingsource.com'
						),
					),						
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
		);
		
	protected $static_thankyou = TRUE;		

	public function Generate_Fields(&$lead_data, &$params)
	{

		$payperiod = array(
			'WEEKLY' => 'weekly',
			'BI_WEEKLY' => 'biweekly',
			'TWICE_MONTHLY' => 'twice-monthly',
			'MONTHLY' => 'monthly',
		);

		$frequency = '';
		if (isset($lead_data['data']['paydate']['frequency']))
		{
			$frequency = $payperiod[$lead_data['data']['paydate']['frequency']];
		}
		if (!$frequency && isset($lead_data['data']['income_frequency']))
		{
			$frequency = $payperiod[$lead_data['data']['income_frequency']];
		}
		if (!$frequency && isset($lead_data['data']['paydate_model']['income_frequency']))
		{
			$frequency = $payperiod[$lead_data['data']['paydate_model']['income_frequency']];
		}

		if (
			!empty($lead_data['data']['name_first']) &&
			!empty($lead_data['data']['name_last']) &&
			!empty($lead_data['data']['home_street']) &&
			!empty($lead_data['data']['home_city']) &&
			!empty($lead_data['data']['home_state']) &&
			!empty($lead_data['data']['home_zip']) &&
			!empty($lead_data['data']['phone_home']) &&
			!empty($lead_data['data']['email_primary']) &&
			!empty($lead_data['data']['ssn_part_1']) &&
			!empty($lead_data['data']['ssn_part_2']) &&
			!empty($lead_data['data']['ssn_part_3']) &&
			!empty($lead_data['data']['state_id_number']) &&
			!empty($lead_data['data']['date_dob_y']) &&
			!empty($lead_data['data']['date_dob_m']) &&
			!empty($lead_data['data']['date_dob_d']) &&
			!empty($lead_data['data']['income_direct_deposit']) &&
			!empty($lead_data['data']['employer_name']) &&
			!empty($lead_data['data']['phone_work']) &&
			!empty($lead_data['data']['paydates'][0]) &&
			!empty($lead_data['data']['paydates'][1]) &&
			!empty($lead_data['data']['income_monthly_net']) &&
			!empty($lead_data['data']['income_type']) &&
			!empty($frequency) &&
			!empty($lead_data['data']['bank_name']) &&
			!empty($lead_data['data']['bank_aba']) &&
			!empty($lead_data['data']['bank_account']) &&
			!empty($lead_data['data']['bank_account_type']) &&
			!empty($lead_data['data']['ref_01_name_full']) &&
			!empty($lead_data['data']['ref_01_relationship']) &&
			!empty($lead_data['data']['ref_01_phone_home']) &&
			!empty($lead_data['data']['ref_02_name_full']) &&
			!empty($lead_data['data']['ref_02_relationship']) &&
			!empty($lead_data['data']['ref_02_phone_home']) &&
			!empty($lead_data['data']['client_ip_address'])
			)
		{

			$fields =
				"first name: {$lead_data['data']['name_first']}\n".
				"last name: {$lead_data['data']['name_last']}\n".
				"address: {$lead_data['data']['home_street']}\n".
				"city: {$lead_data['data']['home_city']}\n".
				"state: {$lead_data['data']['home_state']}\n".
				"zip: {$lead_data['data']['home_zip']}\n".
				"home phone: {$lead_data['data']['phone_home']}\n".
				"email: {$lead_data['data']['email_primary']}\n".
				"ssn: ".$lead_data['data']['ssn_part_1'].$lead_data['data']['ssn_part_2'].$lead_data['data']['ssn_part_3']."\n".
				"drivers license: {$lead_data['data']['state_id_number']}\n".
				"dob: ".$lead_data['data']['date_dob_y'].'/'.$lead_data['data']['date_dob_m'].'/'.$lead_data['data']['date_dob_d']."\n".
				"direct deposit or paper check: ".($lead_data['data']['income_direct_deposit'] == 'TRUE' ? 'direct deposit' : 'paper check')."\n".
				"employer: {$lead_data['data']['employer_name']}\n".
				"work phone: {$lead_data['data']['phone_work']}\n".
				"first and second payday:\n".
					"   ".date("Y-m-d", strtotime($lead_data['data']['paydates'][0]))."\n".
					"   ".date("Y-m-d", strtotime($lead_data['data']['paydates'][1]))."\n".
				"gross monthly: {$lead_data['data']['income_monthly_net']}\n".
				"source of income: {$lead_data['data']['income_type']}\n".
				"pay cycle: {$frequency}\n".
				"bank name: {$lead_data['data']['bank_name']}\n".
				"bank aba #: {$lead_data['data']['bank_aba']}\n".
				"bank account number: {$lead_data['data']['bank_account']}\n".
				"account type: {$lead_data['data']['bank_account_type']}\n".
				"two references:\n".
					"   ".$lead_data['data']['ref_01_name_full']." (".$lead_data['data']['ref_01_relationship'].") ".$lead_data['data']['ref_01_phone_home']."\n".
					"   ".$lead_data['data']['ref_02_name_full']." (".$lead_data['data']['ref_02_relationship'].") ".$lead_data['data']['ref_02_phone_home']."\n".
				"ip address : {$lead_data['data']['client_ip_address']}\n";

		}
		else
		{
			$fields = NULL;
		}

		return $fields;
	}
	
	public function Generate_Result(&$data_received, &$cookies)
	{
		return "";
	}

	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [Statesville Investment Services]";
	}
	
	public function Thank_You_Content(&$data_received)
	{

		$content = '<br/>Thank you for your application. You have been pre-approved  with one of '
			. 'our lending partners.';
		return($content);
	}

	// This function will override the post process in the parent.  SIS
	// would like to receive their leads in email for the time being.
	public function HTTP_Post_Process($fields, $qualify = FALSE)
	{

		$result = new Vendor_Post_Result();
		
		if ( is_null($fields) )
		{
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
			$result->Set_Data_Sent($fields);
			$result->Set_Data_Received('Mail Sent? No');
			$result->Set_Vendor_Decision('REJECTED');
		}
		else
		{
			require_once("prpc/client.php");
			$data = array(
				'message' => $fields,
				'subject' => 'SIS Lead Delivery',
				'sender_name' => 'Lead Delivery <no-reply@sellingsource.com>',
			);
			
			require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
			$tx = new OlpTxMailClient(false);

			$mail_failed = false;
            $last_data = null;
            //$data['application_id'] = $this->getApplicationId();
			foreach ($this->params['recipients'] as $recipient)
			{
				$send_data = array_merge($recipient, $data);
				if(USE_TRENDEX)
				{
					try 
					{
						$result = $tx->sendMessage('live','Lead_Delivery_Tier_2_PLUS',
							$send_data['email_primary'],'',$send_data);
							
					}
					catch (Exception $e)
					{
						$mail_failed = true;
						$last_data = $send_data;
					}
				}
				else 
				{
					if(!($sent = $mail->Ole_Send_Mail("Lead_Delivery_Tier_2_PLUS", 31631, $send_data)))
                	{
                    	$mail_failed = true;
                    	$last_data = $send_data;
                	}
				}
			}
            if($mail_failed)
            {
                $ole_applog = OLP_Applog_Singleton::Get_Instance(APPLOG_OLE_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE);
                $ole_applog->Write("OLE Send Mail failed. Last message: \n" . print_r($last_data,true) . "\nCalled from " . __FILE__ . ":" . __LINE__);
            }

			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content( $data_received ) );
			$result->Set_Data_Sent($fields);
			$result->Set_Data_Received('Mail Sent? Yes');
			$result->Set_Vendor_Decision('ACCEPTED');
		}


		return $result;
	}
	
}
