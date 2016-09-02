<?php

	class ECash_OLPApplicationAdapter
	{
		/**
		 * @var array
		 */
		protected $data;
	
		public function __construct(array $data)
		{
			$this->data = $data;
		}

		public function __get($name)
		{
			switch ($name)
			{
				case 'ip_address': return $data['client_ip_address'];
				case 'name_first': return $this->data['name_first'];
				case 'name_last': return $this->data['name_last'];
				case 'email': return $this->data['email_primary'];
				case 'phone_home': return $this->data['phone_home'];
				case 'phone_work': return $this->data['phone_work'];
				case 'phone_cell': return $this->data['phone_cell'];
				case 'phone_work_ext': return (empty($data['ext_work']) ? NULL : $data['ext_work']);
				case 'call_time_pref': return strtolower($data['bast_call_time']);
				case 'street': return $this->data['home_street'];
				case 'unit': return $this->data['home_unit'];
				case 'city': return $this->data['home_city'];
				case 'state': return $this->data['home_state'];
				case 'zip_code': return $this->data['home_zip'];
				case 'employer_name': return $this->data['employer_name'];
				case 'date_hire': return date('Y-m-d', strtotime('-3 months')); //this one is brilliant
				case 'legal_id_number': return $this->data['state_id_number'];
				case 'legal_id_state': return ($data['state_issued_id'] ? $data['state_issued_id'] : $data['home_state']);
				case 'legal_id_type': return 'dl';
				case 'income_direct_deposit': return ((strtoupper($this->data['income_direct_deposit']) == 'TRUE') ? 'yes' : 'no');
				case 'income_source': return $this->data['income_type'];
				case 'income_frequency': return $this->data['paydate_model']['income_frequency'];
				case 'bank_name': return $this->data['bank_name'];
				case 'bank_account_type': return $this->data['bank_account_type'];
				case 'income_monthly': return $this->data['income_monthly_net'];
				case 'ssn': return $this->data['social_security_number'];
				case 'dob': return strtotime($data['dob']);				
				case 'bank_aba': return $this->data['bank_aba'];
				case 'bank_account': return $this->data['bank_account'];
				case 'paydate_model': return $this->data['paydate_model']['model_name'];
				case 'olp_process': return $this->data['olp_process'];
				case 'application_id': return $this->data['application_id'];
				case 'track_id': return $this->data['track_key'];
				case 'phone_fax': return (empty($data['phone_fax']) ? NULL : $data['phone_fax']);
				case 'application_type': return 'paperless';
				case 'date_fund_estimated': return strtotime($data['qualify_info']['fund_date']);
				case 'date_first_payment': return strtotime($data['qualify_info']['payoff_date']);
				case 'fund_qualified': return $this->data['qualify_info']['fund_amount'];
				case 'finance_charge': return $this->data['qualify_info']['finance_charge'];
				case 'payment_total': return $this->data['qualify_info']['total_payments'];
				case 'apr': return $this->data['qualify_info']['apr'];
				case 'income_monthly':
					return (isset($data['qualify_info']['monthly_net']) ? $data['qualify_info']['monthly_net'] : $data['income_monthly_net']);
				case 'is_react': return ((isset($data['react']) || isset($data['reckey'])) ? 'yes' : 'no');
				case 'pwadvid': return (isset($data['pwadvid']) ? $data['pwadvid'] : NULL);
				case 'enterprise_site_id': return ECash_API::getSiteID($data['ent_config']->license);
			}
		}
		
		public function __isset($name)
		{
			return ($this->__get($name) !== NULL);
		}
	}

?>