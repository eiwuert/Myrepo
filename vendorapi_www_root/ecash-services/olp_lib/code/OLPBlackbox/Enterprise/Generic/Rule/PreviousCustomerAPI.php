<?php

/**
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomerAPI extends OLPBlackbox_Rule
{
	/**
	 * @var bool
	 */
	protected $should_expire;
	
	/**
	 * OLPECash_VendorAPI instance
	 *
	 * @var OLPECash_VendorAPI
	 */
	protected $api;

	/**
	 * Whether applications should be expired
	 * 
	 * @param OLPECash_VendorAPI $api
	 * @param bool $should_expire
	 */
	public function __construct(OLPECash_VendorAPI $api, $should_expire)
	{
		$this->api = $api;
		$this->should_expire = $should_expire;
	}

	/**
	 * Determines if the rule has enough data to run; this rule can always run
	 *
	 * @param Blackbox_Data $data an object with all application data
	 * @param Blackbox_IStateData $state_data an object with target and campaign state data
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}

	/**
	 * Executes the rule
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return bool
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$config = $this->getConfig();
		$property_short = EnterpriseData::resolveAlias($state_data->name);

		/* @var $data OLPBlackbox_Data */
		$ecash_data = $data->toECashArray();
		$ecash_data['mode'] = $config->blackbox_mode;
		$ecash_data['campaign'] = $state_data->campaign_name;
		$ecash_data['is_react'] = (bool)$config->react_company;

		try
		{
			$response = $this->api->previousCustomer($ecash_data);
		}
		catch (Exception $e)
		{
			// must be a Blackbox_Exception for proper onError operation...
			throw new Blackbox_Exception($e->getMessage());
		}

		if (!$response['outcome']
			|| !isset($response['result']))
		{
			throw new Blackbox_Exception('Invalid API response');
		}

		$result = $response['result'];

		if (isset($result['is_react'])
			&& $result['is_react'])
		{
			$state_data->is_react = TRUE;
			$state_data->react_app_id = $result['react_application_id'];
		}

		$this->hitEvents($result, $state_data);

		return (isset($result['valid'])
			&& $result['valid']);
	}

	/**
	 * Hits old-school events for Previous Customer checks
	 * Stolen from the Qualify rule... :(
	 * @param array $result
	 * @param Blackbox_IStateData $state_data
	 * @return void
	 */
	protected function hitEvents(array $result, Blackbox_IStateData $state_data)
	{
		$config = $this->getConfig();

		if (isset($result['customer_history'])
			&& is_array($result['customer_history']))
		{
			$value = NULL;

			foreach ($result['customer_history'] as $check => $value)
			{
				$this->hitEvent(
					OLPBlackbox_Config::EVENT_PREV_CUSTOMER.'_'.$check,
					$value,
					$data->application_id,
					$state_data->campaign_name,
					$config->blackbox_mode
				);
			}

			// overall event, for compatibility
			if (isset($value))
			{
				$this->hitEvent(
					OLPBlackbox_Config::EVENT_PREV_CUSTOMER,
					$value,
					$data->application_id,
					$state_data->campaign_name,
					$config->blackbox_mode
				);
			}
		}

		// Hit DNL events
		if (isset($result['dnl']) && is_array($result['dnl']))
		{
			foreach ($result['dnl'] AS $company)
			{
				$this->hitEvent(
					'DNL_HIT',
					$company,
					$data->application_id,
					$state_data->campaign_name,
					$config->blackbox_mode
				);
			}
		}

		// Hit DNL override events
		if (isset($result['dnlo']) && is_array($result['dnlo']))
		{
			foreach ($result['dnlo'] AS $company)
			{
				$this->hitEvent(
					'DNL_OVERRIDE_HIT',
					$company,
					$data->application_id,
					$state_data->campaign_name,
					$config->blackbox_mode
				);
			}
		}
	}
}

?>
