<?php

/**
 * A specialized collection of all the prevous customer rules.
 *
 * This class embodies all of the various previous customer rules into a
 * single rule that is added to an enterprise target collection. This
 * class takes care of combining histories, factorying the proper customer
 * providers and deciders, and allows the individual enterprise companies
 * to personalize the checks that are run.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomer implements OLPBlackbox_IRule
{
	/**
	 * @var array
	 */
	protected $rules = array();

	/**
	 * @var OLPBlackbox_Enterprise_ICustomerHistoryProvider
	 */
	protected $provider;

	/**
	 * @var OLPBlackbox_Enterprise_ICustomerDecider
	 */
	protected $decider;

	/**
	 * @var string
	 */
	protected $event;

	/**
	 * Property short of the owner of the enterprise site
	 *
	 * @var string
	 */
	protected $enterprise;

	/**
	 * @var bool
	 */
	protected $expire_apps = FALSE;

	/**
	 * @param OLPBlackbox_Enterprise_ICustomerHistoryProvider $provider
	 * @param OLPBlackbox_Enterprise_ICustomerHistoryDecider $decider
	 * @param string $enterprise Owner of enterprise site
	 * @param bool $expire_apps
	 * @return void
	 */
	public function __construct(
		OLPBlackbox_Enterprise_ICustomerHistoryProvider $provider,
		OLPBlackbox_Enterprise_ICustomerHistoryDecider $decider,
		$enterprise = NULL,
		$expire_apps = FALSE
	)
	{
		$this->provider = $provider;
		$this->decider = $decider;
		$this->enterprise = $enterprise;
		$this->expire_apps = $expire_apps;
	}

	/**
	 * Sets the event log event name that will be used
	 *
	 * @param string $event
	 * @return void
	 */
	public function setEventName($event)
	{
		$this->event = $event;
	}

	/**
	 * Evaluates the rule and returns whether it was valid
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_StateData $state
	 * @return bool
	 */
	public function isValid(Blackbox_Data $data, Blackbox_StateData $state)
	{
		if (!isset($state->customer_history)
			|| !$state->customer_history instanceof OLPBlackbox_Enterprise_CustomerHistory)
		{
			throw new Blackbox_Exception('Customer history was not found');
		}

		$valid = TRUE;
		$this->initRules();

		foreach ($this->rules as $r)
		{
			$valid = $r->isValid($data, $state);
			if ($valid === FALSE) break;
		}

		/* @var $history OLPBlackbox_Enterprise_CustomerHistory */
		$history = $state->customer_history;

		// expire applications when we're supposed to
		if ($this->expire)
		{
			$apps = $history->getExpirableApplications();
			$this->expireApplications($apps);
		}

		// @todo add event log
		$result = $this->decider->getDecision($history);
		$log->logEvent($this->event, $result);

		return $this->decider->isValid($result);
	}

	/**
	 * Initialize the rules that this company will use
	 * @todo probably simplify this
	 * @return void
	 */
	protected function initRules()
	{
		// add the checks that the company wants to run here
		$this->addRule(new OLPBlackbox_Enterprise_Rule_PreviousCustomer_SSN($this->provider, $this->decider));
		$this->addRule(new OLPBlackbox_Enterprise_Rule_PreviousCustomer_EmailDOB($this->provider, $this->decider));
	}

	/**
	 * Adds a rule to the internal collection
	 *
	 * @param Blackbox_IRule $r
	 * @return bool
	 */
	protected function addRule(OLPBlackbox_Enterprise_Rule_PreviousCustomer $r)
	{
		$r->setEnterprise($this->enterprise);
		$r->setEventName($this->event);

		$this->rules[] = $r;
	}

	/**
	 * Expires OLP and LDB applications
	 *
	 * @param array $apps
	 * @return void
	 */
	protected function expireApplications(array $apps)
	{
		// @todo get database
		$app = new App_Campaign_Manager();

		// expire OLP apps and collect app IDs by company
		foreach ($apps as $info)
		{
			$app->Update_Application_Status($info['application_id'], 'EXPIRED');
			$ldb[$info['company']][] = $info['app_id'];
		}

		// now expire all the eCash applications
		foreach ($ldb as $company=>$apps)
		{
			$l = OLP_LDB::Get_Object($company);
			$l->Expire_Applications($apps);
		}
	}
}

?>
