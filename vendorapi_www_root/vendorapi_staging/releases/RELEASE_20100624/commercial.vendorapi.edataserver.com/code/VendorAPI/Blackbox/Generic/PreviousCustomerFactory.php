<?php

/**
 * A factory for the previous customer checks
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Generic_PreviousCustomerFactory
{
	/**
	 * Gets an instance of this factory
	 *
	 * @param string $enterprise
	 * @param string $company
	 * @param VendorAPI_IDriver $driver
	 * @param Blackbox_Config $config
	 * @param string $withdrawn_threshold
	 * @return VendorAPI_Blackbox_Generic_PreviousCustomerFactory
	 */
	public static function getInstance($enterprise, $company, VendorAPI_IDriver $driver, Blackbox_Config $config, $withdrawn_threshold = NULL)
	{
		switch (strtolower($enterprise))
		{
			case 'clk':
				return new VendorAPI_Blackbox_CLK_PreviousCustomerFactory($company, $driver, $config, $withdrawn_threshold);
			case 'hms':
				return new VendorAPI_Blackbox_HMS_PreviousCustomerFactory($company, $driver, $config, $withdrawn_threshold);
			case 'agean':
				return new VendorAPI_Blackbox_Agean_PreviousCustomerFactory($company, $driver, $config, $withdrawn_threshold);
			case 'impact':
				return new VendorAPI_Blackbox_Impact_PreviousCustomerFactory($company, $driver, $config, $withdrawn_threshold);
			case 'aalm':
				return new VendorAPI_Blackbox_AALM_PreviousCustomerFactory($company, $driver, $config, $withdrawn_threshold);
			case 'opm':
				return new VendorAPI_Blackbox_OPM_PreviousCustomerFactory($company, $driver, $config, '-3 days');
			case 'rrv':
				return new VendorAPI_Blackbox_RRV_PreviousCustomerFactory($company, $driver, $config, $withdrawn_threshold);
		}
		return new self($company, $driver, $config);
	}

	/**
	 * The number of active loans allowed _at the time of the check_; this should
	 * be the number of active loans allowed minus 1 (the loan they're applying for)
	 * for instance, Impact customers are allowed 1 active loan at any given
	 * time -- which would be the loan they're applying for -- thus their active
	 * threshold is zero, not one
	 *
	 * @var int
	 */
	protected $active_threshold = 0;

	/**
	 * strtotime value of the time to look for denied applications
	 *
	 * @var string
	 */
	protected $denied_time_threshold = '-30 days';

	/**
	 * The number of disagreed loans within the time threshold to allow
	 *
	 * @var unknown_type
	 */
	protected $disagreed_threshold = 0;

	/**
	 * strtotime value of the time to look for denied applications
	 *
	 * @var string
	 */
	protected $disagreed_time_threshold = '-48 hours';

	const EVENT_PREFIX = 'PREV_CUST_';
	const STAT_PREFIX = 'prev_cust';

	/**
	 * @var string
	 */
	protected $target_name;

	/**
	 * @var VendorAPI_IDriver
	 */
	protected $driver;

	/**
	 * @var Blackbox_Config
	 */
	protected $config;

	/**
	 * @var bool
	 */
	protected $react = FALSE;
	
	/**
	 * @var string
	 */
	protected $olp_process;	

        /**
	 * @var string
	 */	         
	protected $react_type;

	/**
	 * @var string
	 */
	protected $company;

	/**
	 * @var VendorAPI_Blackbox_ICustomerHistoryDecider
	 */
	protected $decider;

	/**
	 * Holds the strtotime compatible threshold for withdrawn apps if needed
	 *
	 * @example '-1 day' to check 1 day previous for withdrawn apps
	 * @var string
	 */
	protected $withdrawn_threshold;

	/**
	 * Rules that will be set skippable.
	 *
	 * @var array
	 */
	protected $skippable_rules = array('License');

	/**
	 * @param string $target_name
	 * @param VendorAPI_IDriver $driver
	 * @param Blackbox_Config $config
	 * @param string $withdrawn_threshold strtotime compatible string
	 */
	public function __construct($target_name, VendorAPI_IDriver $driver, Blackbox_Config $config, $withdrawn_threshold = NULL)
	{
		$this->target_name = $target_name;
		$this->driver = $driver;
		$this->config = $config;
		$this->withdrawn_threshold = $withdrawn_threshold;
	}

	/**
	 * Gets the overal previous customer rule
	 *
	 * @param ECash_CustomerHistory $customer_history
	 * @return Blackbox_IRule
	 */
	public function getPreviousCustomerRule(ECash_CustomerHistory $customer_history)
	{
		$this->company = $this->config->company;
		$this->react = $this->config->is_react;
		$this->olp_process = $this->config->olp_process;
		$this->react_type = $this->config->react_type;	

		$criteria = new VendorAPI_PreviousCustomer_CriteriaContainer();

		// Reacts may have different rules to run for previous customer checks.
		// Originally added for GForge #8688 [DW]
		if ($this->config->is_react)
		{
			$this->addReactCriteria($criteria);
		}
		else
		{
			$this->addCriteria($criteria);
		}

		$loader = $this->getCustomerHistoryLoader($criteria);

		$is_enterprise = ($this->config->blackbox_mode !== VendorAPI_Blackbox_Config::MODE_AGREE && $this->config->is_enterprise);

		return new VendorAPI_Blackbox_Rule_PreviousCustomer($this->config->event_log, $customer_history, $this->getDecider(), $loader, $this->company, $this->config->is_react, $is_enterprise);
	}

	/**
	 * Gets an instance of the customer history loader.
	 *
	 * This is the thing that pulls data from the app service and populates a customer history object.
	 * 
	 * @param VendorAPI_PreviousCustomer_CriteriaContainer $criteria
	 * @return VendorAPI_PreviousCustomer_HistoryLoader
	 */
	protected function getCustomerHistoryLoader(VendorAPI_PreviousCustomer_CriteriaContainer $criteria)
	{
		return new VendorAPI_PreviousCustomer_HistoryLoader($this->driver->getAppClient(), $this->getCustomerHistoryStatusMap(), $criteria);
	}

	/**
	 * Adds the individual previous customer criteria to the container
	 *
	 * @param VendorAPI_PreviousCustomer_CriteriaContainer $container
	 * @return void
	 */
	protected function addCriteria(VendorAPI_PreviousCustomer_CriteriaContainer $container)
	{
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_Ssn($this->getCustomerHistoryStatusMap()));
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_EmailDob($this->getCustomerHistoryStatusMap()));
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_HomePhone($this->getCustomerHistoryStatusMap()));
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_BankAccountDob($this->getCustomerHistoryStatusMap()));
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_License($this->getCustomerHistoryStatusMap()));
	}

	/**
	 * Returns an instance of the customer history status map.
	 *
	 * This is necessary for understanding status groupings.
	 * 
	 * @return VendorAPI_PreviousCustomer_CustomerHistoryStatusMap
	 */
	protected function getCustomerHistoryStatusMap()
	{
		return new VendorAPI_PreviousCustomer_CustomerHistoryStatusMap();
	}

	/**
	 * Adds the individual react previous customer criteria to the container
	 * By default, this just calls $this->addCriteria().
	 *
	 * @param VendorAPI_PreviousCustomer_CriteriaContainer $container
	 * @return void
	 */
	protected function addReactCriteria(VendorAPI_PreviousCustomer_CriteriaContainer $container)
	{
		$this->addCriteria($container);
	}

	/**
	 * Gets the company's decider
	 *
	 * @return VendorAPI_Blackbox_Generic_Decider
	 */
	protected function getDecider()
	{
		return new VendorAPI_Blackbox_Generic_Decider(
			$this->active_threshold,
			$this->denied_time_threshold,
			$this->disagreed_threshold,
			$this->disagreed_time_threshold,
			$this->withdrawn_threshold,
			$this->config->company
		);
	}

	/**
	 * Set the active threshold for the factory
	 *
	 * @param int $threshold
	 * @return void
	 */
	public function setActiveThreshold($threshold)
	{
		$this->active_threshold = $threshold;
	}

	/**
	 * Set the denied time as a valid strtotime statement threshold for the factory
	 *
	 * @param string $threshold
	 * @return void
	 */
	public function setDeniedTimeThreshold($threshold)
	{
		$this->denied_time_threshold = $threshold;
	}

	/**
	 * Set the disagreed threshold for the factory
	 *
	 * @param int $threshold
	 * @return void
	 */
	public function setDisagreedThreshold($threshold)
	{
		$this->disagreed_threshold = $threshold;
	}

	/**
	 * Set the disagreed time threshold as a valid strtotime statement for the factory
	 *
	 * @param string $threshold
	 * @return void
	 */
	public function setDisagreedTimeThreshold($threshold)
	{
		$this->disagreed_time_threshold = $threshold;
	}

	/**
	 * Set the withdrawn threshold for the factory
	 *
	 * @param int $threshold
	 * @return void
	 */
	public function setWithdrawnThreshold($threshold)
	{
		$this->withdrawn_threshold = $threshold;
	}
}

?>
