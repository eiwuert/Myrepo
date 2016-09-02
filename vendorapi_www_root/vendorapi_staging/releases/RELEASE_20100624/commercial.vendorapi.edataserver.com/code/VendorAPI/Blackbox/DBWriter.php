<?php

/**
 * writes submitted applications and results to the database
 *
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class VendorAPI_Application_log_DBWriter
{
	protected $config;

	protected $db;

	public function __construct()
	{
        $this->config = ECash::getConfig();
		$this->db = $this->getDatabase();
        
	}

	public function writeApplicationResult($data, $result)
	{
		foreach ($data as $key => $value) {
			if (preg_match('/\\\\/', $value)){
				$value = preg_replace('/\\\\/', '', $value);
			}
			$value = str_replace('"', '\"', $value);
			//$value = str_replace("'","\'",$value);
			$data->$key = $value;
		}

		$sql_str = 'INSERT INTO submitted_applications '
		    .'(date_created, result, application_id, name_last, name_first, name_middle, ssn, dob, dob_y, dob_m, dob_d, email, phone_cell, phone_home, home_street, street, home_unit, unit, home_city, city, home_state, state, county, home_zip, zip_code, residence_start_date, legal_id_number, legal_id_state, income_frequency, income_source, employer_name, phone_work, date_hire, job_title, income_monthly_net, income_monthly, military, bank_name, bank_aba, bank_account, banking_start_date, bank_account_type, direct_deposit, income_direct_deposit, requested_amount, fund_requested, qualified_loan_amount, date_first_payment, client_ip_address, ip_address, client_url_root, campaign_name, promo_id, promo_sub_code, track_id, external_id, lead_cost, vendor_customer_id, rule_set_id, ioBlackBox, olp_process, react_type, react_application_id) '
		    .'VALUES (NOW(),"'.$result.'", "'.$data->application_id.'", "'.$data->name_last.'", "'.$data->name_first.'", "'.$data->name_middle.'", "'.$data->ssn.'", "'.$data->dob.'", "'.$data->dob_y.'", "'.$data->dob_m.'", "'.$data->dob_d.'", "'.$data->email.'", "'.$data->phone_cell.'", "'.$data->phone_home.'", "'.$data->home_street.'", "'.$data->street.'", "'.$data->home_unit.'", "'.$data->unit.'", "'.$data->home_city.'", "'.$data->city.'", "'.$data->home_state.'", "'.$data->state.'", "'.$data->county.'", "'.$data->home_zip.'", "'.$data->zip_code.'", "'.$data->residence_start_date.'", "'.$data->legal_id_number.'", "'.$data->legal_id_state.'", "'.$data->income_frequency.'", "'.$data->income_source.'", "'.$data->employer_name.'", "'.$data->phone_work.'", "'.$data->date_hire.'", "'.$data->job_title.'", "'.$data->income_monthly_net.'", "'.$data->income_monthly.'", "'.$data->military.'", "'.$data->bank_name.'", "'.$data->bank_aba.'", "'.$data->bank_account.'", "'.$data->banking_start_date.'", "'.$data->bank_account_type.'", "'.$data->direct_deposit.'", "'.$data->income_direct_deposit.'", "'.$data->requested_amount.'", "'.$data->fund_requested.'", "'.$data->qualified_loan_amount.'", "'.$data->date_first_payment.'", "'.$data->client_ip_address.'", "'.$data->ip_address.'", "'.$data->client_url_root.'", "'.$data->campaign_name.'", "'.$data->promo_id.'", "'.$data->promo_sub_code.'", "'.$data->track_id.'", "'.$data->external_id.'", "'.$data->lead_cost.'", "'.$data->vendor_customer_id.'", "'.$data->rule_set_id.'", "'.$data->ioBlackBox.'", "'.$data->olp_process.'", "'.$data->react_type.'", "'.$data->react_application_id.'" )';
		$result = $this->db->query($sql_str);
	}
	/**
	 * Gets a database connection
	 *
	 * This will attempt to connect to each defined database in the failover order
	 *
	 * @return DB_IConnection_1
	 */
	public function getDatabase()
	{

		if (!$this->db)
		{
			$db = new DB_FailoverConfig_1();
			if (!$this->use_master)
			{
				$db->addConfig($this->config->DB_API_CONFIG);
				$db->addConfig($this->config->DB_SLAVE_CONFIG);
			}
			$db->addConfig($this->config->DB_MASTER_CONFIG);
			$this->db = $db->getConnection();
		}
		return $this->db;
	}
}

?>
