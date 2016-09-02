<?php

/**
 * VendorAPI Blackbox Data
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class VendorAPI_Blackbox_Data extends Blackbox_Data
{
	/**
	 * Blackbox_Data constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		// only keys initialized here will be allowed changed/set later.
		$this->data['application_id'] = NULL;			// LDB Application Column
		$this->data['name_first'] = NULL;				// LDB Application Column
		$this->data['name_last'] = NULL;				// LDB Application Column
		$this->data['name_middle'] = NULL;				// LDB Application Column
		$this->data['ssn'] = NULL;						// LDB Application Column
		$this->data['dob'] = NULL; 						// LDB Application Column
		$this->data['dob_y'] = NULL; 					// Non LDB Column
		$this->data['dob_m'] = NULL; 					// Non LDB Column
		$this->data['dob_d'] = NULL; 					// Non LDB Column
		$this->data['phone_home'] = NULL;				// LDB Application Column
		$this->data['bank_aba'] = NULL;					// LDB Application Column
		$this->data['bank_account'] = NULL;				// LDB Application Column
		$this->data['permutated_bank_account'] = NULL; 	// Non LDB Column
		$this->data['email'] = NULL;					// LDB Application Column
		$this->data['legal_id_number'] = NULL;			// LDB Application Column
		$this->data['home_street'] = NULL;				// Non LDB Column
		$this->data['street'] = NULL;					// LDB Application Column
		$this->data['home_city'] = NULL;				// Non LDB Column
		$this->data['city'] = NULL;						// LDB Application Column
		$this->data['home_state'] = NULL; 				// Non LDB Column
		$this->data['state'] = NULL; 					// LDB Application Column
		$this->data['home_zip'] = NULL; 				// Non LDB Column
		$this->data['zip_code'] = NULL; 				// LDB Application Column
		$this->data['phone_home'] = NULL;				// LDB Application Column
		$this->data['phone_cell'] = NULL;				// LDB Application Column
		$this->data['phone_work'] = NULL;				// LDB Application Column
		$this->data['home_unit'] = NULL;				// Non LDB Column
		$this->data['unit'] = NULL;						// LDB Application Column
		$this->data['bank_name'] = NULL;				// LDB Application Column
		$this->data['income_frequency'] = NULL;			// LDB Application Column
		$this->data['client_ip_address'] = NULL;		// Non LDB Column
		$this->data['ip_address'] = NULL;				// LDB Application Column
		$this->data['esig_ip_address'] = NULL;				// LDB Application Column
		$this->data['employer_name'] = NULL;			// LDB Application Column
		$this->data['client_url_root'] = NULL;			// Non LDB Column
		$this->data['promo_id'] = NULL;					// LDB Campaign Info Column
		$this->data['income_monthly_net'] = NULL;		// Non LDB Column
		$this->data['income_monthly'] = NULL;			// LDB Application Column
		$this->data['direct_deposit'] = NULL;			// Non LDB Column
		$this->data['income_direct_deposit'] = NULL;	// LDB Application Column
		$this->data['requested_amount'] = NULL;			// Non LDB Column
		$this->data['fund_requested'] = NULL;			// LDB Application Column
		$this->data['legal_id_state'] = NULL;			// LDB Application Column
		$this->data['income_source'] = NULL;			// LDB Application Column
		$this->data['react_application_id'] = NULL;
		$this->data['paydates'] = NULL;                 // Non LDB Column
		$this->data['date_first_payment'] = NULL;
		$this->data['track_id'] = NULL;
		$this->data['personal_reference'] = NULL;
		$this->data['promo_sub_code'] = NULL;
		$this->data['vehicle_year'] = NULL;
		$this->data['vehicle_make'] = NULL;
		$this->data['vehicle_model'] = NULL;
		$this->data['vehicle_series'] = NULL;
		$this->data['vehicle_style'] = NULL;
		$this->data['vehicle_mileage'] = NULL;
		$this->data['vehicle_vin'] = NULL;
		$this->data['bank_account_type'] = NULL;
		$this->data['date_hire'] = NULL;
		$this->data['residence_start_date'] = NULL;
		$this->data['banking_start_date'] = NULL;
		$this->data['military'] = NULL;
		$this->data['external_id'] = NULL;
		$this->data['campaign_name'] = NULL;			// LDB Campaign Info Column for [#50342]
		$this->data['rule_set_id'] = NULL;				// LDB Application Column for [#48935]
		$this->data['job_title'] = NULL;				// LDB Application Column for [#55534]

		// used for DataX checks
		$this->data['lead_cost'] = NULL;				// Non LDB Column
		$this->data['qualified_loan_amount'] = NULL;
		$this->data['vendor_customer_id'] = NULL;
		$this->data['ioBlackBox'] = NULL;				// Non LDB Column
		$this->data['olp_process'] = NULL;				// Non LDB Column
		$this->data['react_type'] = NULL;				// Non LDB Column
	}

	/**
	 * Load from an array source. Handles mapping of keys.
	 *
	 * @param array $data
	 * @return void
	 */
	public function loadFrom(array $data)
	{
		foreach ($this->data as $key => $value)
		{
			if (isset($data[$key]))
			{
				$this->data[$key] = $data[$key];
			}
		}

		if (isset($data['dob']) && (!isset($data['dob_y']) || !isset($data['dob_m']) || !isset($data['dob_d'])))
		{
			list($this->data['dob_y'], $this->data['dob_m'], $this->data['dob_d']) = split("-", date('Y-m-d', strtotime($data['dob'])));
		}

		if (isset($this->data['bank_account']))
		{
			$this->data['permutated_bank_account'] = $this->permutateAccount($this->data['bank_account']);
		}

		/**
		 * Aliased versions of the Application Values for use with the suppression lists [#54143]
		 */
		if(isset($this->data['city'])) $this->data['home_city'] = $this->data['city'];
		if(isset($this->data['state'])) $this->data['home_state'] = $this->data['state'];
		if(isset($this->data['zip_code'])) $this->data['home_zip'] = $this->data['zip_code'];
		if(isset($this->data['ip_address'])) $this->data['client_ip_address'] = $this->data['ip_address'];
		if(isset($this->data['esig_ip_address'])) $this->data['esig_ip_address'] = $this->data['esig_ip_address'];

	}

	/**
	 * Returns an array of all the data
	 * @return array
	 */
	public function toArray()
	{
		return $this->data;
	}

	/**
	 * Generates all possible 0-padded variations for a bank account number
	 *
	 * A bank account number can have up to 17 numbers, potentially left
	 * padded with zeroes. Comparisons should ignore the padding.
	 *
	 * @param int $bank_account The bank account number
	 * @return array A list of account number permutations
	 */
	protected function permutateAccount($bank_account)
	{
		$base = ltrim($bank_account, '0');
		$accounts = array();

		for ($i = strlen($base); $i <= 17; $i++)
		{
			$accounts[] = $base;
			$base = '0'.$base;
		}

		return $accounts;
	}
}

?>
