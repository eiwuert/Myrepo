<?php

/**
 * @desc A concrete implementation class for posting to efm, bmg172
 */
class Vendor_Post_Impl_BMG extends Abstract_Vendor_Post_Implementation
{
	
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
              'post_url' => 'https://eleadweb.thebmggroup.com/directpost.aspx', // Changed per GForge #6881 [BA]
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'https://eleadweb.thebmggroup.com/directpost.aspx', // Changed per GForge #6881 [BA]
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'efm'    => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'c_id' => '167',
					),
				),
			'bmg172'    => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'c_id' => '172',
					),
				),
				
			'bmg178'  => Array
				(
					// while this is in testing, we can post to the "live" URL
					'ALL' => array('c_id' => '178'),
					// NOTE: Use the below CID for testing the new implmentation (GForge #6881) [BA]
					// 'ALL' => array('c_id' => 35),
				),
			'bmg178_2'  => Array
				(
					// while this is in testing, we can post to the "live" URL
					'ALL' => array('c_id' => '201'),
				),
				
		);
	
	protected $static_thankyou = TRUE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		
		$epd_map = array (
			'WEEKLY' => 'Weekly',
			'MONTHLY' => 'Monthly',
			'BI_WEEKLY' => 'Bi-Weekly',
			'TWICE_MONTHLY' => 'Semi-Monthly',
		);
		$Employee_Days_Paid = $epd_map[$lead_data['data']['paydate_model']['income_frequency']];
		
		$url = $params['post_url'];
		
		$issued_state = ($lead_data['data']['state_issued_id']) ? $lead_data['data']['state_issued_id'] : $lead_data['data']['home_state'];
		
		
		
		
		
		$fields = array (
			'C' => $params['c_id'],
			'First_Name' => $lead_data['data']['name_first'],
			'Last_Name' => $lead_data['data']['name_last'],
			'Address' => $lead_data['data']['home_street'].($lead_data['data']['home_unit'] ? ' '.$lead_data['data']['home_unit'] : ''),
			'City'	=> $lead_data['data']['home_city'],
			'State' => $lead_data['data']['home_state'],
			'Zip' => $lead_data['data']['home_zip'],
			'Email' => $lead_data['data']['email_primary'],
			'Future_Offers' => $lead_data['data']['offers'] == "TRUE" ? 1 : 0,
			'Alt_Phone' => substr($lead_data['data']['phone_work'],0,3).'-'.substr($lead_data['data']['phone_work'],3,3).'-'.substr($lead_data['data']['phone_work'],6,4),
			'Gender' => 'M',
			'DOB'	=>  $lead_data['data']['date_dob_m'].'-'.$lead_data['data']['date_dob_d'].'-'.$lead_data['data']['date_dob_y'],
			'Employer' => $lead_data['data']['employer_name'],
			'Direct_Deposit' => $lead_data['data']['income_direct_deposit'] == 'TRUE' ? 'Y' : 'N',
			'Employee_Income' => $lead_data['data']['income_monthly_net'],
			'Employee_Days_Paid' => $Employee_Days_Paid,
			'Employee_Next_Pay_Day' => date("m-d-Y", strtotime($lead_data['data']['paydates'][0])),
			'Employee_Next_Next_Pay_Day' => date("m-d-Y", strtotime($lead_data['data']['paydates'][1])),
			'Bank_ABA' => $lead_data['data']['bank_aba'],
			'Phone' => substr($lead_data['data']['phone_home'],0,3).'-'.substr($lead_data['data']['phone_home'],3,3).'-'.substr($lead_data['data']['phone_home'],6,4),
			'IPAddress' => $lead_data['data']['client_ip_address'],
			'SSN' => substr($lead_data['data']['social_security_number'],0,3).'-'.substr($lead_data['data']['social_security_number'],3,2).'-'.substr($lead_data['data']['social_security_number'],5,4),
			'Account_Number' => $lead_data['data']['bank_account'],
			'DL_Number' => $lead_data['data']['state_id_number'],
			'DL_State' => $issued_state,
			'DLN' => $lead_data['data']['state_id_number'],
			'DLS' => $issued_state,
			'URL' => SiteConfig::getInstance()->site_name, // Added GForge #6881 [BA]
			'USER_IP' => $lead_data['data']['client_ip_address'],
			'Cell_Phone' => $lead_data['data']['phone_cell'],
			'Work_Ext' => $lead_data['data']['ext_work'],
			'Reference_Name_1' => $lead_data['data']['ref_01_name_full'],
			'Reference_Phone_1' => $lead_data['data']['ref_01_phone_home'],
			'Reference_Relationship_1' => $lead_data['data']['ref_01_relationship'],
			'Reference_Name_2' => $lead_data['data']['ref_02_name_full'],
			'Reference_Phone_2' => $lead_data['data']['ref_02_phone_home'],
			'Reference_Relationship_2' => $lead_data['data']['ref_02_relationship'],
			
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
		elseif((preg_match('/Status=(\d+);/is', $data_received, $m) && $m[1] == '0') || (!$data_received))
		{
			$re = $m[0];
			
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
		return "Vendor Post Implementation [BMG]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		// Changing to redirect to the URL found in the receive data GForge #6881 [BA]
		preg_match('/[^;]+;[^;]+;(.+)/is', $data_received, $m);
		$url = trim($m[1]);
		$content = parent::Generic_Thank_You_Page($url, self::REDIRECT);
		return($content);
	}
	
}
