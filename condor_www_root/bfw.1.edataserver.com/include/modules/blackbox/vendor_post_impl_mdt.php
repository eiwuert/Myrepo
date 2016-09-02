<?php

/**
 * A concrete implementation class for posting to Media Trust (mdt)
 * @see http://gforge.sellingsource.com/gf/project/epoint/tracker/?action=TrackerItemEdit&tracker_id=195&tracker_item_id=4506 New Tier 2 BBx Campaign - Media Trust (mdt)
 */
class Vendor_Post_Impl_MDT extends Abstract_Vendor_Post_Implementation
{

	protected $rpc_params  = array(
        'ALL' => array(
            'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/MDT',
            'FeedID' => '00650000009thKUAAY',
            ),
        'LOCAL' => array(),
        'RC' => array(),
        'LIVE' => array(
            'post_url' => 'https://pts.mediatrust.com',
            ),
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
		$soap_site_types = array ('blackbox.one.page','soap_oc','soap','soap_no_esig');
		$source_url = (in_array(strtolower($_SESSION['config']->site_type), $soap_site_types)) ?
			'123onlinecash.com' :
			$_SESSION['config']->site_name;
		
		$payperiod = array(
			'WEEKLY' => 'Weekly',
			'BI_WEEKLY' => 'Bi-Weekly',
			'TWICE_MONTHLY' => 'Semi-Monthly',
			'MONTHLY' => 'Monthly',
		);

		if (!empty($lead_data['data']['paydate']['frequency'])) {
			$_tmp['payfrequency'] = $payperiod[$lead_data['data']['paydate']['frequency']];
		} elseif (!empty($lead_data['data']['income_frequency'])) {
			$_tmp['payfrequency'] = $payperiod[$lead_data['data']['income_frequency']];
		} elseif (!empty($lead_data['data']['paydate_model']['income_frequency'])) {
			$_tmp['payfrequency'] = $payperiod[$lead_data['data']['paydate_model']['income_frequency']];
		} else {
			$_tmp['payfrequency'] = ''; // NO such type, but this is a required field.
		}

		$fields = array (
			'FeedID' => $params['FeedID'], // 18 char FeedID specified above.
			'source_url' => $source_url, // alphanumeric 100 char max (i.e. "source_url=www.site.com")
			'redirect' => '', // your thank you page (i.e. "http://www.yourthankyoupage.com" or empty)
			'firstname' => $lead_data['data']['name_first'], // alphanumeric 50 char max
			'lastname' => $lead_data['data']['name_last'], // alphanumeric 50 char max
			'email' => $lead_data['data']['email_primary'], // alphanumeric 50 char max
			'address1' => $lead_data['data']['home_street'], // alphanumeric 50 char max
			'address2' => '', // alphanumeric 50 char max
			'city' => $lead_data['data']['home_city'], // alphanumeric 50 char max
			'state' => $lead_data['data']['home_state'], // alpha 2 char max
			'zipcode' => $lead_data['data']['home_zip'], // numeric 5 digits with leading zeroes
			'ipaddress' => $lead_data['data']['client_ip_address'], // ip address
			'homephone_area_code' => substr($lead_data['data']['phone_home'], 0, 3), // 3 numeric max
			'homephone_prefix' => substr($lead_data['data']['phone_home'], 3, 3), // 3 numeric max
			'homephone_suffix' => substr($lead_data['data']['phone_home'], 6, 4), // 4 numeric max
			'workphone_area_code' => substr($lead_data['data']['phone_work'], 0, 3), // 3 numeric max
			'workphone_prefix' => substr($lead_data['data']['phone_work'], 3, 3), // 3 numeric max
			'workphone_suffix' => substr($lead_data['data']['phone_work'], 6, 4), // 4 numeric max
			'ssn' => preg_replace('/^([\d]{3})([\d]{2})([\d]{4})$/', '$1-$2-$3', $lead_data['data']['social_security_number']), // format: 123-12-1234
			'dob' => "{$lead_data['data']['date_dob_m']}/{$lead_data['data']['date_dob_d']}/{$lead_data['data']['date_dob_y']}", // format: 12/31/1980
			'licensenumber' => $lead_data['data']['state_id_number'], // alphanumeric 50 char max
			'licensestate' => $lead_data['data']['state_issued_id'], // alpha 2 char max
			'monthlyincome' => $lead_data['data']['income_monthly_net'], // numeric, no $ signs or commas
			'payfrequency' => $_tmp['payfrequency'], // Monthly, Weekly, Bi-Weekly, Semi-Monthly
			'directdeposit' => (strcasecmp($lead_data['data']['income_direct_deposit'], 'TRUE') === 0) ? 'Y' : 'N', // Y or N
			'paydate1' => date("m/d/Y", strtotime($lead_data['data']['paydates'][0])), // format: 12/31/1980
			'paydate2' => date("m/d/Y", strtotime($lead_data['data']['paydates'][1])), // format: 12/31/1980
			'bankname' => $lead_data['data']['bank_name'], // alphanumeric 50 char max
			'routingnumber' => $lead_data['data']['bank_aba'], // numeric 50 char max, no spaces or dashes
			'bankaccountnumber' => $lead_data['data']['bank_account'], // numeric 50 char max
			'bankaccounttype' => $lead_data['data']['bank_account_type'], // alphanumeric 50 char max
			'referencename1' => $lead_data['data']['ref_01_name_full'], // alphanumeric 50 char max
			'referencephone1' => preg_replace('/^([\d]{3})([\d]{2})([\d]{4})$/', '$1-$2-$3', $lead_data['data']['social_security_number']), // format: 123-123-1234
			'employername' => $lead_data['data']['employer_name'], // alphanumeric 50 char max
			'incomesource' => (strcasecmp($lead_data['data']['income_type'], 'EMPLOYMENT') === 0) ? 'EMPLOYED' : 'DISABILITY_BENEFITS', // PENSION, DISABILITY_BENEFITS, SOCIAL_SECURITY, UNEMPLOYED, EMPLOYED, TEMP_AGENCY
			'gender' => '',
					
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
			$data = preg_replace('/^((.*)\n)?([^\n]+)$/ims', '$3', $data_received);
			$matches = array();
			preg_match('/^(SUCCESS|FAILURE)(([\s]+-[\s]+)(\S.*\S)?)?$/i', trim($data), $matches);

			if (strcasecmp($matches[1], 'SUCCESS') === 0) {
				$result->Set_Message("Accepted");
				$result->Set_Success(TRUE);
				if (isset($matches[4]) && !empty($matches[4])) {
					if (preg_match('/^[^;\s]+\s*;\s*([\S]*)$/i', trim($matches[4]), $matches2)) {
						$url = trim($matches2[1]);
					} else {
						$url = NULL;
					}
					$result->Set_Thank_You_Content( self::Thank_You_Content($url) );
				}
				$result->Set_Vendor_Decision('ACCEPTED');
			} else {
				$result->Set_Message("Rejected");
				$result->Set_Success(FALSE);
				$result->Set_Vendor_Decision('REJECTED');
				if (isset($matches[4]) && !empty($matches[4])) {
					$result->Set_Vendor_Reason($matches[4]);
				}
			}
		}

		return $result;
	}

	/**
	 * A PHP magic function.
	 *
	 * @return string a string describing this class.
	 */
	public function __toString() {
		return "Vendor Post Implementation [mdt]";
	}

	/**
	 * Return a "Thank You" message back.
	 *
	 * @param string $data_received URL used for redirecting.
	 * @return string a "Thank You" message.
	 */
	public function Thank_You_Content(&$data_received) {
		$content = parent::Generic_Thank_You_Page( $data_received, self::REDIRECT );
		
		return $content;
	}

}
