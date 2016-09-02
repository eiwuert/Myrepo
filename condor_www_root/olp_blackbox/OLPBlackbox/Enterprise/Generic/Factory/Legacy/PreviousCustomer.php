<?php

/**
 * A factory for the previous customer checks
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_Factory_Legacy_PreviousCustomer
{
	// the number of active loans allowed _at the time of the check_; this should
	// be the number of active loans allowed minus 1 (the loan they're applying for)
	// for instance, Impact customers are allowed 1 active loan at any given
	// time -- which would be the loan they're applying for -- thus their active
	// threshold is zero, not one
	const ACTIVE_THRESHOLD = 0;

	const DENIED_THRESHOLD = '-30 days';

	// Add disagreed threshold to check for disagreed apps - GForge #8774 [DW]
	const DISAGREED_THRESHOLD = 1;

	/**
	 * Gets the correct instance of the previous customer factory for the given target
	 *
	 * @param string $target_name
	 * @param OLPBlackbox_Config $config
	 * @return OLPBlackbox_Enterprise_Generic_Factory_Legacy_PreviousCustomer
	 */
	public static function getInstance($target_name, OLPBlackbox_Config $config)
	{
		// companies that have a single parent target
		switch (strtolower($target_name))
		{
			case EnterpriseData::COMPANY_CLK:
				return new OLPBlackbox_Enterprise_CLK_Factory_Legacy_PreviousCustomer($target_name, $config);
		}

		// companies that have don't have a parent target
		switch (EnterpriseData::getCompany($target_name))
		{
			case EnterpriseData::COMPANY_CLK:
				return new OLPBlackbox_Enterprise_CLK_Factory_Legacy_PreviousCustomer($target_name, $config);

			case EnterpriseData::COMPANY_IMPACT:
				return new OLPBlackbox_Enterprise_Impact_Factory_Legacy_PreviousCustomer($target_name, $config);

			case EnterpriseData::COMPANY_AGEAN:
				return new OLPBlackbox_Enterprise_Agean_Factory_Legacy_PreviousCustomer($target_name, $config);
		}

		return new self($target_name, $config);
	}

	/**
	 * @var string
	 */
	protected $target_name;

	/**
	 * @var OLPBlackbox_Config
	 */
	protected $config;

	/**
	 * @var bool
	 */
	protected $react = FALSE;

	/**
	 * @var string
	 */
	protected $enterprise;

	/**
	 * @var OLPBlackbox_Enterprise_ICustomerHistoryProvider
	 */
	protected $olp_provider;

	/**
	 * @var OLPBlackbox_Enterprise_ICustomerHistoryProvider
	 */
	protected $ecash_provider;

	/**
	 * @var OLPBlackbox_Enterprise_ICustomerHistoryDecider
	 */
	protected $decider;

	/**
	 * @param string $target_name
	 * @param OLPBlackbox_Config $config
	 */
	public function __construct($target_name, OLPBlackbox_Config $config)
	{
		$this->target_name = $target_name;
		$this->config = $config;
	}

	/**
	 * Gets the overal previous customer rule
	 *
	 * @return Blackbox_IRule
	 */
	public function getPreviousCustomerRule()
	{
		// skip if we're skipping
		if ($this->config->debug->debugSkipRule(OLPBlackbox_DebugConf::PREV_CUSTOMER))
		{
			return new OLPBlackbox_DebugRule(OLPBlackbox_Config::EVENT_PREV_CUSTOMER);
		}

		// @todo Preact should not be in the _DEBUG_ configuration
		$this->enterprise = ($this->config->is_enterprise ? $this->config->bb_force_winner : NULL);
		$this->react = ($this->config->blackbox_mode === OLPBlackbox_config::MODE_ECASH_REACT);

		// during a react, the previous customer checks
		// are restricted to the enterprise company
		$companies = ($this->react)
			? array($this->config->react_company)
			: $this->getCompanies();

		$this->olp_provider = $this->getOLPProvider($companies);
		$this->ecash_provider = $this->getECashProvider($companies);
		$this->decider = $this->getDecider();

		$collection = $this->getCollection();
		$this->addRules($collection);

		return $collection;
	}

	/**
	 * By default, run for all companies within the enterprise
	 * @return array
	 */
	protected function getCompanies()
	{
		$enterprise = EnterpriseData::getCompany($this->target_name);
		return EnterpriseData::getCompanyProperties($enterprise);
	}

	/**
	 * Adds the individual previous customer rules to the collection
	 *
	 * @param OLPBlackbox_Enterprise_Generic_PreviousCustomerCollection $prev_cust
	 * @return void
	 */
	protected function addRules(OLPBlackbox_Enterprise_Generic_PreviousCustomerCollection $prev_cust)
	{
		// enterprise/single company mode is not set in agree
		$set_enterprise = ($this->config->blackbox_mode !== OLPBlackbox_Config::MODE_AGREE);

		$rules = array(
			$this->getRule('SSN', $set_enterprise),
			$this->getRule('EmailDob', $set_enterprise),
			$this->getRule('HomePhone'),
			$this->getRule('BankAccount'),
			$this->getRule('License')
		);

		foreach ($rules as $rule)
		{
			if ($this->config->blackbox_mode != OLPBlackbox_Config::MODE_BROKER)
			{
				$rule->setSkippable(TRUE);
			}

			$prev_cust->addRule($rule);
		}
	}

	/**
	 * Gets the previous customer rule collection
	 *
	 * @return OLPBlackbox_Enterprise_Generic_PreviousCustomerCollection
	 */
	protected function getCollection()
	{
		$collection = new OLPBlackbox_Enterprise_Generic_PreviousCustomerCollection($this->react);
		$collection->setEventName(OLPBlackbox_Config::EVENT_PREV_CUSTOMER);
		$collection->setStatName(OLPBlackbox_Config::STAT_PREV_CUSTOMER);

		return $collection;
	}

	/**
	 * creates a basic rule
	 *
	 * @param string $name
	 * @param bool $ent
	 * @return Blackbox_IRule
	 */
	protected function getRule($name, $ent = FALSE)
	{
		$class = 'OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomer_'.$name;
		$enterprise = ($ent ? $this->enterprise : NULL);

		$rule = new $class($this->olp_provider, $this->ecash_provider, $this->decider, $enterprise);
		return $rule;
	}

	/**
	 * Gets the ECash provider
	 *
	 * @param array $companies
	 * @return OLPBlackbox_Enterprise_ECashProvider
	 */
	protected function getECashProvider(array $companies)
	{
		$provider = new OLPBlackbox_Enterprise_Generic_ECashProvider(
			$companies
		);
		return $provider;
	}

	/**
	 * Gets the OLP provider
	 *
	 * @param array $companies
	 * @return OLPBlackbox_Enterprise_OLPProvider
	 */
	protected function getOLPProvider(array $companies)
	{
		$provider = new OLPBlackbox_Enterprise_Generic_OLPProvider(
			$this->getOLPConnection(),
			$companies
		);
		return $provider;
	}

	/**
	 * Gets the company's decider
	 *
	 * @return OLPBlackbox_Enterprise_ICustomerHistoryDecider
	 */
	protected function getDecider()
	{
		return new OLPBlackbox_Enterprise_Generic_Decider(
			self::ACTIVE_THRESHOLD,
			self::DENIED_THRESHOLD
		);
	}

	/**
	 * Gets a connection to the OLP database
	 * @return DB_IConnection_1
	 */
	protected function getOLPConnection()
	{
		/* @var $wrapped MySQL_Wrapper */
		$wrapped = $this->config->olp_db;
		return new DB_MySQL4Adapter_1($wrapped->getConnection(), $wrapped->db_info['db']);
	}

	/**
	 * Gets the LDB connection for a CLK company
	 *
	 * @param string $company Company name, used to make LDB connection.
	 * @return DB_IConnection_1
	 */
	protected function getLDBConnection($company)
	{
		/* @var $wrapped MySQL_Wrapper */
		$wrapped = Setup_DB::Get_Instance('mysql', $this->config->mode, $company);
		return new DB_MySQLiAdapter_1($wrapped->getConnection());
	}
}

?>
