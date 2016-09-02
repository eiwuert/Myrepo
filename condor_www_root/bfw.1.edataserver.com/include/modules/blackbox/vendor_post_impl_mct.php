<?php
/**
 * vendor_post.php
 */
class Vendor_Post_Impl_MCT extends Abstract_Vendor_Post_Implementation
{
	
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
				'post_url' => 'https://www.mycashtime.com/interface/standard'
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				'post_url' => 'http://www.mycashtime.com/interface/standard'
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'https://www.mycashtime.com/interface/standard'
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'mct'    => Array(
				'ALL'      => Array(
					'post_url' => 'http://dev.mycashtime.com/interface/standard', // Testing Only
					//'post_url' => 'https://www.mycashtime.com/interface/standard', // Live Only
					'company' => '1',
					'password' => 'password',
					'source' => '38',
					'tracking_id' => '',
					),
				'LOCAL'    => Array(
					'post_url' => 'http://www.mycashtime.com/interface/standard'
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.mycashtime.com/interface/standard'
					),
				),
		);
	
	protected $static_thankyou = FALSE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		// Split reference 1 name
    $ref1Array = split(" ", $lead_data['data']['ref_01_name_full']);
    $ref1FirstName = $ref1Array[0];
    $ref1LastName = "";
    for($i = 1; $i < count($ref1Array); $i++){
    	if($i > 1){
    		$ref1LastName .= " ";
    	}
    	$ref1LastName .= $ref1Array[$i];
    }
    // Split reference 2 name
    $ref2Array = split(" ", $lead_data['data']['ref_02_name_full']);
    $ref2FirstName = $ref2Array[0];
    $ref2LastName = "";
    for($i = 1; $i < count($ref2Array); $i++){
    	if($i > 1){
    		$ref2LastName .= " ";
    	}
    	$ref2LastName .= $ref2Array[$i];
    }
   	
    // Split home address street number and name
    $addArray = split(" ", $lead_data['data']['home_street']);
    $addressNumber = $addArray[0];
    $addressStreet = "";
    for($i = 1; $i < count($addArray); $i++){
    	if($i > 1){
    		$addressStreet .= " ";
    	}
    	$addressStreet .= $addArray[$i];
    }
    
    $dob = "{$lead_data['data']['date_dob_y']}-{$lead_data['data']['date_dob_m']}-{$lead_data['data']['date_dob_d']}";
		$fields = array (
			'Company' => $params['company'],
			'Password' => $params['password'],
			'Source' => $params['source'],
			'SSN' => $lead_data['data']['social_security_number'],
			'FirstName' => $lead_data['data']['name_first'],
			'LastName' => $lead_data['data']['name_last'],
			'DLNumber' => $lead_data['data']['state_id_number'],
			'DLState' => $lead_data['data']['state_issued_id'],
			'DateOfBirth' => $dob,
			'Email' => $lead_data['data']['email_primary'],
			'HomePhone' => $lead_data['data']['phone_home'],
			'CellPhone' => $lead_data['data']['phone_cell'],
			'WorkPhone' => $lead_data['data']['phone_work'],
			'WorkPhoneExt' => $lead_data['data']['ext_work'],
			'StreetNumber' => $addressNumber,
			'StreetName' => $addressStreet,
			'City' => $lead_data['data']['home_city'],
			'State' => $lead_data['data']['home_state'],
			'Zip' => $lead_data['data']['home_zip'],
			'EmployerName' => $lead_data['data']['employer_name'],
			'MonthlyIncome' => $lead_data['data']['income_monthly_net'],
			'PayFrequency' => $lead_data['data']['paydate']['frequency'],
			'NextPayDate' => date("m/d/Y", strtotime($lead_data['data']['paydate_model']['next_pay_date'])),
			'FollowingPayDate' => date("m/d/Y", strtotime($lead_data['data']['paydates'][1])),
			'DirectDeposit' => ($lead_data['data']['income_direct_deposit'] == 'TRUE') ? 'Y' : 'N',
			'BankName' => $lead_data['data']['bank_name'],
			'BankAccountType' => $lead_data['data']['bank_account_type'],
			'RoutingNumber' => $lead_data['data']['bank_aba'],
			'AccountNumber' => $lead_data['data']['bank_account'],
			'Contact1FirstName' => $ref1FirstName,
			'Contact1LastName' => $ref1LastName,
			'Contact1Phone1' => $lead_data['data']['ref_01_phone_home'],
			'Contact2FirstName' => $ref2FirstName,
			'Contact2LastName' => $ref2LastName,
			'Contact2Phone1' => $lead_data['data']['ref_02_phone_home'],
			'IPAddress' => $lead_data['data']['ip_address'],
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
		elseif ( preg_match( '/Status=Success/', $data_received ) )
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content($data_received) );
			$result->Set_Vendor_Decision('ACCEPTED');
		}
		else
		{
			// Determine Error Code
			preg_match("/ErrorCode=(\d*)/",$data_received,$error_code);
			
			$error_messages = array("No Errors","Source is Incorrect","Password Incorrect","CompanyID is incorrect","SSN is incorrect (#########)","Missing first name","Missing last name","Missing email","Monthly income incorrect (1 – 6)","Pay frequency incorrect (1 - 7)","Next pay date incorrect (YYYY-MM-DD)","Direct Deposit indicator incorrect (Y or N)","Missing bank name","Bank account type incorrect (C or S)","Missing routing numb","Missing bank account number","Missing phone number (Home,Cell or Work)","Missing driver’s license number","Driver’s license state incorrect","Date of birth is incorrect","Missing street number","Missing street name","Missing city","Missing state code","Missing zip code","Database error");
			
			$result->Set_Message("Rejected: " . $error_messages[$error_code[1]]);
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
		return "Vendor Post Implementation [MCT]";
	}
	
	public static function Thank_You_Content(&$data_received)
	{
		//Parse cookie
		$m = array();
		preg_match('/AppID=([a-z0-9-]{36})/i', $data_received, $m);
		$r_url = "https://loanwebforms.com/displayagreement.aspx?appid=" . $m[1];
		
		return parent::Generic_Thank_You_Page($r_url);
	}
}

?>