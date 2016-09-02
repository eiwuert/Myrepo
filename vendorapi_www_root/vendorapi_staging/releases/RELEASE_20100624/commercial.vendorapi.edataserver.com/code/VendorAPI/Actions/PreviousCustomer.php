<?php

/**
 * Stub for Previous Customer
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class VendorAPI_Actions_PreviousCustomer extends VendorAPI_Actions_Base
{
	
	protected $application_factory;

	public function __construct(VendorAPI_IDriver $driver,
		VendorAPI_IApplicationFactory $application_factory
	)
	{
		parent::__construct($driver);
		$this->application_factory = $application_factory;

	}
	
	/**
	 * Executes the Qualify action
	 *
	 * Current result:
	 * array(
	 *   'amount' => (int)[qualify amount]
	 * )
	 *
	 * @param array $data Application data
	 * @param VendorAPI_StateObject $serialized_state
	 * @return VendorAPI_Response
	 */
	public function execute(array $data, $serialized_state = NULL)
	{
		$state = new VendorAPI_StateObject();

		if (!$this->isValid($data, $state))
		{
			return new VendorAPI_Response(
				$state,
				VendorAPI_Response::SUCCESS,
				array('is_valid' => FALSE)
			);
		}

		$hist = new ECash_CustomerHistory();
		$bbx_config = $this->getBlackboxConfig($data, $state);
		$rule = $this->getRule($bbx_config, $hist);

		$bbx_data = $this->getBlackboxData($data);
		$bbx_state = new VendorAPI_Blackbox_StateData();

		$valid = $rule->isValid($bbx_data, $bbx_state);

		/* @var $hist ECash_CustomerHistory */
		$company = $this->driver->getCompany();

		return new VendorAPI_Response(
			$state,
			VendorAPI_Response::SUCCESS,
			array(
				'valid' => $valid,
				'is_react' => $hist->getIsReact($company),
				'react_application_id' => $hist->getReactID($company),
				'customer_history' => $hist->getResults(),
				'dnl' => $hist->getDoNotLoan(),
				'dnlo' => $hist->getDoNotLoanOverride(),
				'expirable_applications' => $hist->getExpirableApplications()
			)
		);
	}

	/**
	 * Creates and populates the Blackbox_Config object
	 * @param array $data
	 * @return VendorAPI_Blackbox_Config
	 */
	protected function getBlackboxConfig(array $data, VendorAPI_StateObject $state)
	{
		$bbx_config = new VendorAPI_Blackbox_Config();
		$bbx_config->blackbox_mode = VendorAPI_Blackbox_Config::MODE_AGREE;
		$bbx_config->enterprise = $this->driver->getEnterprise();
		$bbx_config->company = $this->driver->getCompany();
		$bbx_config->campaign = $data['campaign'];
		$bbx_config->is_enterprise = $data['is_enterprise'];
		$bbx_config->is_react = ($data['is_react'] != 'no' && (bool)$data['is_react']);
		$bbx_config->olp_process = $data['olp_process'];
		$bbx_config->react_type = $data['react_type'];
		
		$bbx_config->event_log = new VendorAPI_Blackbox_EventLog(
			$state,
			$data['application_id'],
			$data['campaign']
		);

		return $bbx_config;
	}

	/**
	 * Creates and populates a VendorAPI_Blackbox_Data object
	 * @param array $data
	 * @return VendorAPI_Blackbox_Data
	 */
	protected function getBlackboxData(array $data)
	{
		$bbx_data = new VendorAPI_Blackbox_Data();
		$bbx_data->loadFrom($data);
		return $bbx_data;
	}

	/**
	 * Factories and returns the Previous Customer rule (collection, typically)
	 * @param VendorAPI_Blackbox_Config $config
	 * @param ECash_CustomerHistory
	 * @return Blackbox_IRule
	 */
	protected function getRule(VendorAPI_Blackbox_Config $config, ECash_CustomerHistory $history)
	{
		$factory = $this->driver->getBlackboxRuleFactory($config, NULL);
		$rule = $factory->getPreviousCustomerRule($history);
		return $rule;
	}

	/**
	 * @param array $data
	 * @param VendorAPI_StateObject $state
	 * @return bool
	 */
	protected function isValid(array $data, VendorAPI_StateObject $state)
	{
		return TRUE;
	}
	
	/**
	 * Returns Application Factory.
	 *
	 * @return VendorAPI_IApplicationFactory
	 */
	protected function getApplicationFactory()
	{
		return $this->application_factory;
		
	}
}

?>
