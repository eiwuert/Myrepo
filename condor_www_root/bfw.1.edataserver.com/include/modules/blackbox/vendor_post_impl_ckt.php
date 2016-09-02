<?php

/**
 * A concrete implementation class for posting to ClickTarget, Inc. (ckt)
 */
class Vendor_Post_Impl_CKT extends Abstract_Vendor_Post_Implementation
{

	/**
	 * Must be passed as 2nd arg to Generic_Thank_You_Page: parent::Generic_Thank_You_Page($url, self::REDIRECT);
	 *
	 */
	const REDIRECT = 4;

	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
			    'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/CKT',
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'https://www.24hcashsource.com/bpost.php'
				)
		);

	protected $static_thankyou = FALSE;

	/**
	 * Generate field values for post request.
	 *
	 * @param array $lead_data User input data.
	 * @param array $params Values from $this->rpc_params.
	 * @return array Field values for post request.
	 */
	public function Generate_Fields(&$lead_data, &$params)
	{

		$_tmp['dob'] = $lead_data['data']['date_dob_m'].'/'.$lead_data['data']['date_dob_d'].'/'.$lead_data['data']['date_dob_y'];
		$_tmp['over18'] = (strtotime($_tmp['dob']) < strtotime('-18 years')) ? 'Y' : 'N';

		// No workstartdate field, so we make a "fake" field value here.
		if (strtolower($lead_data['data']['employer_length']) == 'true')
			$_tmp['workstartdate'] = date('m/d/Y', strtotime('-100 days')); // more than 3 months
		else
			$_tmp['workstartdate'] = date('m/d/Y', strtotime('-35 days'));	// more than 1 month

		$_tmp['payrolltype'] = (strtolower($lead_data['data']['income_direct_deposit']) == 'true') ? 'D' : 'P';

		$_tmp['nextpaydate'] = date("m/d/Y", strtotime(reset($lead_data['data']['paydates'])));

		$_tmp['optin'] = (strtolower($lead_data['data']['mh_offer']) == 'true') ? 'yes' : 'no';

		$payperiod = array(
			'WEEKLY' => 'W',
			'BI_WEEKLY' => 'S',
			'TWICE_MONTHLY' => 'B',
			'MONTHLY' => 'M',
		);

		if (!empty($lead_data['data']['paydate']['frequency']))
			$_tmp['payrollfreq'] = $payperiod[$lead_data['data']['paydate']['frequency']];
		elseif (!empty($lead_data['data']['income_frequency']))
			$_tmp['payrollfreq'] = $payperiod[$lead_data['data']['income_frequency']];
		elseif (!empty($lead_data['data']['paydate_model']['income_frequency']))
			$_tmp['payrollfreq'] = $payperiod[$lead_data['data']['paydate_model']['income_frequency']];
		else
			$_tmp['payrollfreq'] = ''; // NO such type, but this is a required field.

		$lead_data['data']['ref_01_name_full'] = trim($lead_data['data']['ref_01_name_full']);
		if ($pos = strrpos($lead_data['data']['ref_01_name_full'], ' ')) {
			$_tmp['reffname'] = substr($lead_data['data']['ref_01_name_full'], 0, $pos);
			$_tmp['reflname'] = substr($lead_data['data']['ref_01_name_full'], $pos+1);
		} else {
			$_tmp['reffname'] = $lead_data['data']['ref_01_name_full'];
			$_tmp['reflname'] = 'N/A';
		}

		$lead_data['data']['ref_02_name_full'] = trim($lead_data['data']['ref_02_name_full']);
		if ($pos = strrpos($lead_data['data']['ref_02_name_full'], ' ')) {
			$_tmp['ref2fname'] = substr($lead_data['data']['ref_02_name_full'], 0, $pos);
			$_tmp['ref2lname'] = substr($lead_data['data']['ref_02_name_full'], $pos+1);
		} else {
			$_tmp['ref2fname'] = $lead_data['data']['ref_02_name_full'];
			$_tmp['ref2lname'] = 'N/A';
		}

		switch (strtolower($lead_data['data']['income_type'])) {
			case 'employment':
				$_tmp['incometype'] = 'P';
				break;
			case 'benefits':
				$_tmp['incometype'] = 'W';
				break;
			default:
				$_tmp['incometype'] = 'O';
				break;
		}

		switch (strtolower($lead_data['data']['bank_account_type'])) {
			case 'checking':
				$_tmp['bankaccttype'] = 'C';
				break;
			case 'savings':
				$_tmp['bankaccttype'] = 'S';
				break;
			default:
				$_tmp['bankaccttype'] = ''; // NO such type, but this is a required field.
				break;
		}

		$fields = array (
			'c1' => '',
			'c2' => '',
			'c3' => '',
			'pub' => '100113',
			'fname' => $lead_data['data']['name_first'],
			'lname' => $lead_data['data']['name_last'],
			'addr1' => $lead_data['data']['home_street'],
			'addr2' => '',
			'city' => $lead_data['data']['home_city'],
			'state' => $lead_data['data']['home_state'],
			'zip' => $lead_data['data']['home_zip'],
			'homephone' => $lead_data['data']['phone_home'],
			'cellphone' => $lead_data['data']['phone_cell'],
			'email' => $lead_data['data']['email_primary'],
			'optin' => $_tmp['optin'],
			'ip' => $lead_data['data']['client_ip_address'],
			'over18' => $_tmp['over18'],
			'dob' => $_tmp['dob'],
			'ssn' => $lead_data['data']['social_security_number'],
			'employername' => $lead_data['data']['employer_name'],
			'workphone' => $lead_data['data']['phone_work'],
			'workext' => $lead_data['data']['ext_work'],
			'incometype' => $_tmp['incometype'],
			'workstartdate' => $_tmp['workstartdate'],
			'paycheckamt' => $lead_data['data']['income_monthly_net'],
			'payrolltype' => $_tmp['payrolltype'],
			'payrollfreq' => $_tmp['payrollfreq'],
			'nextpaydate' => $_tmp['nextpaydate'],
			'bankname' => $lead_data['data']['bank_name'],
			'bankaccttype' => $_tmp['bankaccttype'],
			'bankaba' => $lead_data['data']['bank_aba'],
			'bankacct' => $lead_data['data']['bank_account'],
			'reffname' => $_tmp['reffname'],
			'reflname' => $_tmp['reflname'],
			'refhomephone' => $lead_data['data']['ref_01_phone_home'],
			'refrelationship' => $lead_data['data']['ref_01_relationship'],
			'ref2fname' => $_tmp['ref2fname'],
			'ref2lname' => $_tmp['ref2lname'],
			'ref2homephone' => $lead_data['data']['ref_02_phone_home'],
			'ref2relationship' => $lead_data['data']['ref_02_relationship'],
		);

		return $fields;
	}

	/**
	 * Generate post request results.
	 *
	 * @param string $data_received Data received after post request is sent.
	 * @param unknown $cookies a useless parameter.
	 * @return object a Vendor_Post_Result object.
	 */
	public function Generate_Result(&$data_received, &$cookies)
	{

		$result = new Vendor_Post_Result();

		if (!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		else
		{
			$tmp = explode("\n", $data_received);
			foreach ($tmp as $val) {
				$val = trim($val);
				if (!empty($val))
					$data[substr($val, 0, strpos($val, '='))] = substr($val, strpos($val, '=')+1);
			}

			if ($data['Msg'] == 1) {
				$result->Set_Message("Accepted");
				$result->Set_Success(TRUE);
				$result->Set_Thank_You_Content( self::Thank_You_Content($data['URL']) );
				$result->Set_Vendor_Decision('ACCEPTED');
			} else {
				$result->Set_Message("Rejected");
				$result->Set_Success(FALSE);
				$result->Set_Vendor_Decision('REJECTED');
				$result->Set_Vendor_Reason($data['Reason']);
			}
		}

		return $result;
	}

	/**
	 * A PHP magic function.
	 *
	 * @link http://www.php.net/manual/en/language.oop5.magic.php
	 * @return string a string describing this class.
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [ckt]";
	}

	/**
	 * Return a "Thank You" message back.
	 *
	 * @param string $data_received URL used for redirecting.
	 * @return string a "Thank You" message.
	 */
	public function Thank_You_Content(&$data_received)
	{
		$content = parent::Generic_Thank_You_Page( $data_received, self::REDIRECT );

		return $content;
	}

}
