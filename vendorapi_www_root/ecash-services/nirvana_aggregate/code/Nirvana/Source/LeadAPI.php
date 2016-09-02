<?php
class Nirvana_Source_LeadAPI implements Nirvana_ISource
{
	private $wsdl_url;
	private $username;
	private $password;

	public function setWsdlUrl($url)
	{
		$this->wsdl_url = $url;
	}

	public function setUsername($user)
	{
		$this->username = $user;
	}

	public function setPassword($password)
	{
		$this->password = $password;
	}

	public function getTokens($track_keys, $user, $pass)
	{
		$opts = array();
		if ($this->username) {
			$opts['login'] = $this->username;
			$opts['password'] = $this->password;
		}

		$lead_api = new SoapClient($this->wsdl_url, $opts);

		$requests = array();
		foreach($track_keys as $track_key) {
			$request = array();
			$request['search_criteria'] = $track_key;
			$request['field'] = "track_key";
			$request['requesting_fields'] = array("application_id", "application_created_timestamp", "applicant_name_first"
				, "applicant_name_last", "applicant_email", "applicant_address_street", "applicant_address_unit", "applicant_address_city"
				, "applicant_address_state", "applicant_address_zipcode", "lead_client_ip_address", "original_campaign_url", "company_name_short"
				, "site_name", "company_name", "winner", "lead_id", "lead_created_timestamp");

			$requests[] = $request;
		}

		$leads = $lead_api->findLeadInfo($requests);

		return $this->convertLeadsToTokens($leads);
	}

	protected function convertLeadsToTokens($leads)
	{
		$data = array();

		if (!isset($leads->item)) return $data;

		if (is_object($leads->item)) $leads->item = array($leads->item);

		foreach($leads->item as $lead) {
			$t = $lead->track_key;
			$data[$t] = array();
			$data[$t]['source'] = "ECASH LEAD";
			$data[$t]['session_id'] = $lead->session_id;
			$data[$t]['today'] = date("Y-m-d");
			$data[$t]['usa_today'] = date("m/d/Y");

			if (isset($lead->requested_data_content) && isset($lead->requested_data_content->requested_data)) {
				if (is_object($lead->requested_data_content->requested_data)) {
					$lead->requested_data_content->requested_data = array($lead->requested_data_content->requested_data);
				}

				$r = $lead->requested_data_content->requested_data;

				$data[$t]['originating_address'] = $this->FetchLeadDataField("original_campaign_url", $r);
				$data[$t]['name_first'] = $this->FetchLeadDataField("applicant_name_first", $r);
				$data[$t]['name_last'] = $this->FetchLeadDataField("applicant_name_last", $r);
				$data[$t]['address_street'] = $this->FetchLeadDataField("applicant_address_street", $r);
				$data[$t]['address_unit'] = $this->FetchLeadDataField("applicant_address_unit", $r);
				$data[$t]['address_city'] = $this->FetchLeadDataField("applicant_address_city", $r);
				$data[$t]['address_state'] = $this->FetchLeadDataField("applicant_address_state", $r);
				$data[$t]['address_zipcode'] = $this->FetchLeadDataField("applicant_address_zipcode", $r);
				$data[$t]['ip_address'] = $this->FetchLeadDataField("lead_client_ip_address", $r);
				$data[$t]['email'] = $this->FetchLeadDataField("applicant_email", $r);
				$data[$t]['company_name_short'] = $this->FetchLeadDataField("company_name_short", $r);
				$data[$t]['site_name'] = $this->FetchLeadDataField("site_name", $r);

				if (!is_null($data[$t]['originating_address'])) {
					$data[$t]['start_url'] = preg_match('#^https?://#is', $data[$t]['originating_address'])
						? $data[$t]['originating_address'] : "http://{$data[$t]['originating_address']}";
				} else {
					$data[$t]['start_url'] = "";
				}

				if (!is_null($this->FetchLeadDataField("application_id", $r))) {
					$data[$t]['transaction_id'] = $this->FetchLeadDataField("application_id", $r);

					if (!is_null($data[$t]['site_name'])) {
						$encoded_app_id = urlencode(base64_encode($data[$t]['transaction_id']));
						$data[$t]['react_url'] = sprintf("http://%s/?force_new_session&page=ent_cs_confirm_start&reckey=%s",
							$data[$t]['site_name'],
							$encoded_app_id);
					} else {
						$data[$t]['react_url'] = "";
					}

				} elseif (!is_null($this->FetchLeadDataField("lead_id", $r))) {
					$data[$t]['transaction_id'] = $this->FetchLeadDataField("lead_id", $r);
					$data[$t]['react_url'] = "";
				} else {
					$data[$t]['transaction_id'] = NULL;
					$data[$t]['react_url'] = "";
				}

				if (!is_null($this->FetchLeadDataField("application_created_timestamp", $r))) {
					$data[$t]['transaction_date'] = $this->FetchLeadDataField("application_created_timestamp", $r);
					$data[$t]['application_date'] = date("Y-m-d", $this->FetchLeadDataField("application_created_timestamp", $r));
				} else if (!is_null($this->FetchLeadDataField("lead_created_timestamp", $r))) {
					$data[$t]['transaction_date'] = $this->FetchLeadDataField("lead_created_timestamp", $r);
					$data[$t]['application_date'] = date("Y-m-d", $this->FetchLeadDataField("lead_created_timestamp", $r));
				} else {
					$data[$t]['transaction_date'] = "";
					$data[$t]['application_date'] = "";
				}

				/*
				 * Nirvana wants the application site company name if the
				 * applicaiton has not been sold.  If it has been sold
				 * it wants the company name of the target that bought the
				 * lead.  We cannot get information about any company
				 * but the originating company.  If we sold to a different
				 * company than the originating company, return NULL for
				 * the company name [AE]
				 */
				$winner = $this->FetchLeadDataField("winner", $r);
				$company_name_short = $this->FetchLeadDataField("company_name_short", $r);
				if (empty($winner) || strcasecmp($winner, $company_name_short) === 0) {
					$data[$t]['company_name'] = $this->FetchLeadDataField("company_name", $r);
				} else {
					$data[$t]['company_name'] = NULL;
				}
			}
		}
		return $data;
	}

	protected function FetchLeadDataField($field_name, $requested_data) {
		$value = NULL;
		foreach($requested_data as $data) {
			if ($data->field == $field_name) {
				if (isset($data->value)) {
					$value = $data->value;
				}
				break;
			}
		}
		return $value;
	}

	public function __toString() {
		$url = ($this->username ? "{$this->username}:{$this->password}@" : '').$this->wsdl_url;
		return "Lead API: {$url}";
	}
}