<?php
/**
 * vendor_post.php
 */
class Vendor_Post_Impl_UTC extends Abstract_Vendor_Post_Implementation
{
	
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
				'qualify_post_url' => 'https://fcflnx-int01.yourcashbank.com/Apps/ycb_webcontacts.nsf/ProcessPDL?OpenAgent',
				'post_url' => 'https://fcflnx-int01.yourcashbank.com/Apps/ycb_webcontacts.nsf/ProcessPDL_2?OpenAgent'
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
//				'qualify_post_url' => 'http://blackbox.post.server.ds95.tss:80/p.php/UTC_Q',
//				'post_url' => 'http://blackbox.post.server.ds95.tss:80/p.php/UTC'
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'qualify_post_url' => 'https://fcflnx-int01.yourcashbank.com/Apps/ycb_webcontacts.nsf/ProcessPDL?OpenAgent',
				'post_url' => 'https://fcflnx-int01.yourcashbank.com/Apps/ycb_webcontacts.nsf/ProcessPDL_2?OpenAgent'
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'utc'    => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
				),
		);
	
	protected $static_thankyou = FALSE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		$lead_data['data']['postqualification'] = true;
		if (strtoupper($lead_data['data']['income_type']) == "EMPLOYMENT")
		{
			$employed = "TRUE";
		} else {
			$employed = "FALSE";
		}
		
		$fields = array (
			'AppDate' => date("m/d/Y h:i:s"),
			'SSN1' => $lead_data['data']['ssn_part_1'],
			'SSN2' => $lead_data['data']['ssn_part_2'],
			'SSN3' => $lead_data['data']['ssn_part_3'],
			'FirstName' => $lead_data['data']['name_first'],
			'LastName' => $lead_data['data']['name_last'],
			'Address' => $lead_data['data']['home_street'] .' '. $lead_data['data']['home_unit'],
			'City' => $lead_data['data']['home_city'],
			'State' => $lead_data['data']['home_state'],
			'Zip' => $lead_data['data']['home_zip'],
			'HowLongAddress' => "",      // Don't get from customer
			'HomePhone' => $lead_data['data']['phone_home'],
			'CellPagerNum' => $lead_data['data']['phone_cell'],
			'Employer' => $lead_data['data']['employer_name'],
			'Employed' => $employed,
			'EmployerAddress' => "",		// Don't get from customer
			'EmployerCity' => "",				// Don't get from customer
			'EmployerState' => "",			// Don't get from customer
			'EmployerZip' => "",				// Don't get from customer
			'BusinessPhone' => $lead_data['data']['phone_work'],
			'BusinessPhoneExt' => $lead_data['data']['ext_work'],
			'DateStarted' => "",      // Don't get from customer
			'PayFreq' => $lead_data['data']['paydate_model']['income_frequency'],
			'NextPayDate' => date("m/d/Y", strtotime($lead_data['data']['paydates'][0])),
			'NetSalary' => $lead_data['data']['income_monthly_net'],
			'DirectDeposit' => $lead_data['data']['income_direct_deposit'],
			'BankName' => $lead_data['data']['bank_name'],
			'BankPhone' => "",      // Don't get from customer,
			'DirDepCheckSave' => substr($lead_data['data']['bank_account_type'],0,1),
			'RoutingABANum' => $lead_data['data']['bank_aba'],
			'DirDepAcctNum' => $lead_data['data']['bank_account'],
			'RefName' => $lead_data['data']['ref_01_name_full'],
			'Relationship' => $lead_data['data']['ref_01_relationship'],
			'RefAddress' => "",     // Don't get from customer,
			'RefCity' => "",     		// Don't get from customer,
			'RefState' => "",    		// Don't get from customer,
			'RefZip' => "",      		// Don't get from customer,
			'RefPhone' => $lead_data['data']['ref_01_phone_home'],
		);
		
		return $fields;
	}
	
	/**
	 * Same as the Generate_fields() function except for the pre-qualification request
	 *
	 * @param array $lead_data
	 * @return unknown
	 */
	public static function Generate_Qualify_Fields($lead_data)
	{
		$lead_data['data']['prequalification'] = true;
		if (strtoupper($lead_data['data']['income_type']) == "EMPLOYMENT")
		{
			$employed = "TRUE";
		} else {
			$employed = "FALSE";
		}
		
		$fields = array (
			'Employed' => $employed,
			'DirectDeposit' => $lead_data['data']['income_direct_deposit'],
			'NetSalary' => $lead_data['data']['income_monthly_net'],
			'DirDepCheckSave' => substr($lead_data['data']['bank_account_type'],0,1),
			'State' => $lead_data['data']['home_state'],
			'SSN1' => $lead_data['data']['ssn_part_1'],
			'SSN2' => $lead_data['data']['ssn_part_2'],
			'SSN3' => $lead_data['data']['ssn_part_3'],
		);

		return $fields;
	}
	
	/**
	 * @desc Verify Vendor Posting
	 *	Prequalify Posting to Vedor without sending complete data
	 */
	public function Verify($verify_post_type='HTTP')
	{
		$this->Merge_Params();
		$lead_data = $this->Get_Lead_Data();
		$fields = $this->Generate_Qualify_Fields($lead_data);

		switch ($verify_post_type)
		{

		case 'HTTP':
		default:
			$verify_data = array(
				'Employed' => $fields['Employed'],
				'DirectDeposit' => $fields['DirectDeposit'],
				'NetSalary' => $fields['NetSalary'],
				'DirDepCheckSave' => $fields['DirDepCheckSave'],
				'State' => $fields['State'],
				'SSN1' => $fields['SSN1'],
				'SSN2' => $fields['SSN2'],
				'SSN3' => $fields['SSN3'],
			);
				break;

		}
		$result = $this->HTTP_Post_Process($verify_data,TRUE);
		return $result;
	}

	/**
	 * @desc Checks to see if Vendor will accept Post.
	 *	Will post to vendor ID, SSN, and Email.
	 *	Vendor will reply with XML <accept>TRUE/FALSE</accept>
	 */
	public function Verify_Result($data_received, $cookies)
	{
		$result = new Vendor_Post_Result();

		if (!strlen($data_received))
		{
			$result->Empty_Response();
		}
		elseif (preg_match("/Agent done/", $data_received))
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
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


	
	public function Generate_Result(&$data_received, &$cookies)
	{
		$result = new Vendor_Post_Result();
		
		if (!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		elseif ( preg_match( '/OK/', $data_received ) )
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

	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [UTC]";
	}
	
	public static function Thank_You_Content(&$data_received)
	{
		
		$r_url = "https://fcflnx-int01.yourcashbank.com/application.html";
		
		return parent::Generic_Thank_You_Page($r_url);
	}
}
?>