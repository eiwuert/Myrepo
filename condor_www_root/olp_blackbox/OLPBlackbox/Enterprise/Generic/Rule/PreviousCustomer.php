<?php

/**
 * Base class for the previous customer rules
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
abstract class OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomer extends OLPBlackbox_Rule
{
	/**
	 * @var OLPBlackbox_Enterprise_ICustomerHistoryProvider
	 */
	protected $olp_provider;

	/**
	 * @var OLPBlackbox_Enterprise_ICustomerHistoryProvider
	 */
	protected $ecash_provider;

	/**
	 * @var OLPBlackbox_Enterprise_ICustomerDecider
	 */
	protected $decider;

	/**
	 * When on an enterprise site, should contain the "owner"
	 *
	 * @var string
	 */
	protected $enterprise;

	/**
	 * Returns a short name for the rule
	 * This is combined with the 'master' event name to produce
	 * event names; it's also used to index results in customer
	 * history and, eventually, the session (for BC).
	 *
	 * @return string
	 */
	abstract public function getName();

	/**
	 * Gets the conditions that will be given to the ECash provider
	 *
	 * @param Blackbox_Data $data
	 * @return array
	 */
	abstract protected function getECashConditions(Blackbox_Data $data);

	/**
	 * Gets the conditions that will be given to the OLP provider
	 *
	 * @param Blackbox_Data $data
	 * @return array
	 */
	abstract protected function getOLPConditions(Blackbox_Data $data);

	/**
	 * @param OLPBLackbox_Enterprise_IPreviousCustomerProvider $olp
	 * @param OLPBLackbox_Enterprise_IPreviousCustomerProvider $ecash
	 * @param OLPBlackbox_Enterprise_IPreviousCustomerDecider $decider
	 * @param string $enterprise
	 */
	public function __construct(
		OLPBLackbox_Enterprise_ICustomerHistoryProvider $olp,
		OLPBLackbox_Enterprise_ICustomerHistoryProvider $ecash,
		OLPBlackbox_Enterprise_ICustomerHistoryDecider $decider,
		$enterprise = NULL
	)
	{
		parent::__construct();

		$this->olp_provider = $olp;
		$this->ecash_provider = $ecash;
		$this->decider = $decider;
		$this->enterprise = $enterprise;
	}

	/**
	 * Runs the rule and indicates whether it is valid
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state
	 * @return bool
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		/* @var $history OLPBlackbox_Enterprise_CustomerHistory */
		$history = $state->customer_history;

		try
		{
			$this->getHistory($data, $history);
		}
		catch (Exception $e)
		{
			// translate to a Blackbox_Exception for proper onError ops
			throw new Blackbox_Exception($e->getMessage());
		}

		// if we're running on an enterprise marketing site, and
		// we have a react for that site "owner", remove all
		// history not pertaining to that company
		// @todo this will match on EVERY react check after the first react is found
		if ($this->enterprise
			&& $history->getIsReact($this->enterprise))
		{
			$state->customer_history =
				$history = $history->getCompanyHistory($this->enterprise);
			$this->setSingleCompany($this->enterprise);
		}

		$this->result = $this->decider->getDecision($history);
		$this->hitRuleEvent($this->result, $data, $state);

		return $this->decider->isValid($this->result);
	}

	/**
	 * Returns our result
	 *
	 * @return string
	 */
	public function getResult()
	{
		return $this->result;
	}

	/**
	 * Adds the customer history from both providers
	 *
	 * @param OLPBlackbox_Data $data
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @return void
	 */
	protected function getHistory(OLPBlackbox_Data $data, OLPBlackbox_Enterprise_CustomerHistory $history)
	{
		if (($cond = $this->getECashConditions($data)) !== NULL)
		{
			$this->ecash_provider->excludeApplication($data->application_id);
			$this->ecash_provider->getHistoryBy($cond, $history);
		}

		if (($cond = $this->getOLPConditions($data)) !== NULL)
		{
			$this->olp_provider->excludeApplication($data->application_id);
			$this->olp_provider->getHistoryBy($cond, $history);
		}
	}

	/**
	 * Sets a single company for history
	 *
	 * @param string $company
	 * @return void
	 */
	protected function setSingleCompany($company)
	{
		$this->ecash_provider->setCompany($this->enterprise);
		$this->olp_provider->setCompany($this->enterprise);
	}

	/**
	 * Avoid extraneous stat/event hits; we do that ourselves
	 *
	 * @param Blackbox_Data $data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onValid(Blackbox_Data $data, Blackbox_IStateData $state_data) {}

	/**
	 * Avoid extraneous stat/event hits; we do that ourselves
	 *
	 * @param Blackbox_Data $data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data) {}
}

?>