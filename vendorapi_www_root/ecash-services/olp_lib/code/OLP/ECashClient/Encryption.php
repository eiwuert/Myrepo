<?php

/**
 * OLP Api 2 - eCash Encryption.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_ECashClient_Encryption extends OLP_ECashClient_RPC1
{
	/**
	 * @var array
	 */
	protected static $cache = array();
	
	/**
	 * Returns a verbose description of the driver in human readable form.
	 *
	 * @return string
	 */
	public function getDriverDescription()
	{
		return 'API to access eCash data in an unencrypted format.';
	}
	
	/**
	 * Gets all user data for an application by application id.
	 *
	 * @param int $application_id
	 * @return array
	 */
	public function getApplicationData($application_id)
	{
		$data = $this->getApplicationDataByMethod('getDataByApplicationId', array($application_id));
		
		if (is_array($data))
		{
			$data = $this->mapDataForOLP($data);
		}
		
		return $data;
	}
	
	/**
	 * Gets all user data for an application by track key.
	 *
	 * @param array $track_keys
	 * @return array
	 */
	public function getApplicationDataByTrackKey(array $track_keys)
	{
		$data = $this->getApplicationDataByMethod('getDataByTrackKey', array($track_keys));
		
		if (is_array($data))
		{
			foreach ($data AS $key => $values)
			{
				$data[$key] = $this->mapDataForOLP($values);
			}
		}
		
		return $data;
	}
	
	/**
	 * The main processor for getting application data. Handles caching and
	 * data mapping.
	 *
	 * @param string $method_name
	 * @param array $parameters
	 * @return array
	 */
	protected function getApplicationDataByMethod($method_name, array $parameters)
	{
		$data = $this->getCache($method_name, $parameters);
		
		if ($data === NULL)
		{
			$api = $this->getAPI();
			if ($api)
			{
				$data = call_user_func_array(array($api, $method_name), $parameters);
				
				if (!is_array($data))
				{
					$data = FALSE;
				}
				
				$this->setCache($method_name, $parameters, $data);
			}
			else
			{
				throw new Exception(sprintf("Attempting to call ECash OLP API 2's %s but did not get an API for property_short '%s' and mode '%s'",
					$method_name,
					$this->property_short,
					$this->getMode()
				));
			}
		}
		
		return $data;
	}
	
	/**
	 * The filename of the API.
	 *
	 * @return string
	 */
	protected function getURLFilename()
	{
		return "olp.2.php";
	}
	
	/**
	 * Maps common data returned from eCash to common values for OLP.
	 *
	 * @param array $data
	 * @return array
	 */
	protected function mapDataForOLP(array $data)
	{
		// Data that comes back from eCash isn't in the format we will use elsewhere.
		$data_map = array(
			'address_street' => 'home_street',
			'address_unit' => 'home_unit',
			'address_zipcode' => 'home_zip',
			'address_city' => 'home_city',
			'address_state' => 'home_state',
			'best_call_time' => 'best_call',
			'date_hire' => 'employer_length',
			'legal_id_number' => 'state_id_number',
			'legal_id_state' => 'legal_state',
			'income_monthly' => 'income_monthly_net',
			'income_type' => 'income_source',
			'work_name' => 'employer_name',
			'name' => 'ReturnReason',
			'company_name_short' => array('property_short', 'name_short'), // For CS, need as name_short
			'pdm_next_paydate' => 'last_paydate', // For CS, we need a valid paydate
			
			// Splits DOB into Year, Month, and Day, and copies to date_of_birth
			'dob' => array(
				'/^(?<date_dob_y>\d+)-(?<date_dob_m>\d+)-(?<date_dob_d>\d+)$/',
				'date_of_birth',
			),
			
			// Splits ssn into each of the different parts
			'ssn' => '/^(?<ssn_part_1>\d{3})(?<ssn_part_2>\d{2})(?<ssn_part_3>\d{4})$/',
			
			// Build the esignature from first and last name
			'@%%%name_first%%% %%%name_last%%%' => 'esignature',
		);
		
		$data = array_merge($data, OLP_Util::dataMap($data, $data_map));
		
		return $data;
	}
	
	/**
	 * Get the hash for this method call.
	 *
	 * @param string $method_name
	 * @param array $parameters
	 * @return string
	 */
	protected function hashMethod($method_name, array $parameters)
	{
		if (EnterpriseData::getEnterpriseHostname($this->property_short, 'CACHE_BY_COMPANY'))
		{
			$cache_short = EnterpriseData::getCompany($this->property_short);
		}
		else
		{
			$cache_short = $this->property_short;
		}
		
		$str = sprintf(
			'%s::%s::%s::%s',
			$this->getMode(),
			$cache_short,
			$method_name,
			serialize($parameters)
		);
		
		$hash = md5($str);
		
		return $hash;
	}
	
	/**
	 * Checks the cache and return the data if we found it.
	 *
	 * @param string $method_name
	 * @param array $parameters
	 * @return mixed|FALSE
	 */
	protected function getCache($method_name, array $parameters)
	{
		$data = NULL;
		$hash = $this->hashMethod($method_name, $parameters);
		
		if (isset(self::$cache[$hash]))
		{
			$data = self::$cache[$hash];
		}
		
		return $data;
	}
	
	/**
	 * Caches the data for this call.
	 *
	 * @param string $method_name
	 * @param array $parameters
	 * @param mixed $data
	 * @return void
	 */
	protected function setCache($method_name, array $parameters, $data)
	{
		$hash = $this->hashMethod($method_name, $parameters);
		
		self::$cache[$hash] = $data;
	}
}

?>
