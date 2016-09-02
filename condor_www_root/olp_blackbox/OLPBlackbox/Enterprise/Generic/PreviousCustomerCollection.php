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
class OLPBlackbox_Enterprise_Generic_PreviousCustomerCollection extends OLPBlackbox_RuleCollection
{
	/**
	 * @var array
	 */
	protected $rules = array();

	/**
	 * @var string
	 */
	protected $stat_prefix;

	/**
	 * @var bool
	 */
	protected $expire_apps = FALSE;

	/**
	 * @param OLPBlackbox_Enterprise_ICustomerHistoryDecider $decider
	 * @param bool $expire_apps
	 * @return void
	 */
	public function __construct($expire_apps = FALSE, $stat_prefix = NULL)
	{
		$this->expire_apps = $expire_apps;
		$this->stat_prefix = $stat_prefix;
	}

	/**
	 * Indicates whether applications will be expired
	 * @return bool
	 */
	public function getExpireApplications()
	{
		return $this->expire_apps;
	}

	/**
	 * Adds a rule to the internal collection
	 * NOTE: sets the rule's event to a combination of our own event + the rule name
	 *
	 * @param Blackbox_IRule $r
	 * @return void
	 */
	public function addRule(Blackbox_IRule $rule)
	{
		if (!$rule instanceof OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomer)
		{
			throw new Blackbox_Exception('Rule must be an instance of OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomer');
		}

		/* @var $rule OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomer */
		$rule->setEventName($this->event_name.'_'.$rule->getName());
		$rule->setStatName($this->stat_name.'_'.$rule->getName());
		$this->rules[] = $rule;
	}

	/**
	 * Evaluates the rule and returns whether it was valid
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_StateData $state
	 * @return bool
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		if (!isset($state->customer_history)
			|| !($state->customer_history instanceof OLPBlackbox_Enterprise_CustomerHistory))
		{
			throw new Blackbox_Exception('Customer history was not found');
		}

		/* @var $history OLPBlackbox_Enterprise_CustomerHistory */
		$history = $state->customer_history;
		$valid = TRUE;

		$check_result = NULL;
		/* @var $rule OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomer */
		foreach ($this->rules as $rule)
		{
			$valid = $rule->isValid($data, $state);

			// record results on a per-check basis...
			// these are used later to populate session values
			if ($rule_result = $rule->getResult())
			{
				$check_result = $rule_result;
				$history->setResult($rule->getName(), $rule_result);
			}

			if ($valid === FALSE) break;
		}

		// for backwards compatibility, we only log an event for
		// the entire collection if at least one of our rules runs
		if ($check_result)
		{
			$this->hitEvents(
				$history,
				$check_result,
				$data->application_id,
				$state->name
			);

			$this->hitStats(
				$check_result,
				$data,
				$state
			);
		}
		elseif (!$valid)
		{
			// invalid but no result, which means that a rule was skipped
			// (most likely) but was not skippable. [DO]
			$this->hitEvent(
				$this->event_name.'_SKIP_NOT_SKIPPABLE',
				OLPBlackbox_Config::EVENT_RESULT_FAIL,
				$data->application_id,
				$state->name,
				OLPBlackbox_Config::getInstance()->blackbox_mode
			);
		}

		// applications are only expired during ecash reacts;
		// this will be set in the appropriate mode(s) by the factory
		if ($this->expire_apps)
		{
			$apps = $history->getExpirableApplications();
			$this->expireApplications($apps);
		}

		return $valid;
	}

	/**
	 * Hits all previous customer events
	 *
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @param string $result
	 * @param int $app_id
	 * @param string $target
	 * @param string $mode
	 * @return void
	 */
	protected function hitEvents(OLPBlackbox_Enterprise_CustomerHistory $history, $result, $app_id, $target)
	{
		$mode = OLPBLackbox_Config::getInstance()->blackbox_mode;

		// overall event
		$this->hitEvent($this->event_name, $result, $app_id, $target, $mode);

		// DNL events
		foreach ($history->getDoNotLoan() as $company)
		{
			$this->hitEvent('DNL_HIT', $company, $app_id, $target, $mode);
		}

		// DNLo events
		foreach ($history->getDoNotLoanOverride() as $company)
		{
			$this->hitEvent('DNL_OVERRIDE_HIT', $company, $app_id, $target, $mode);
		}
	}

	/**
	 * Hits the previous customer stats
	 *
	 * @param string $result
	 * @param Blackbox_Data $blackbox_data
	 * @param Blackbox_IStateData $state_data
	 * @return void
	 */
	protected function hitStats($result, Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		if ($this->stat_prefix)
		{
			$this->hitSiteStat($this->stat_prefix.$result, $blackbox_data, $state_data);
		}
	}

	/**
	 * Expires OLP and LDB applications
	 *
	 * @param array $apps
	 * @return void
	 */
	protected function expireApplications(array $apps)
	{
		$config = OLPBlackbox_Config::getInstance();
		$app = new App_Campaign_Manager(
			$config->olp_db,
			$config->olp_db->db_info['db'],
			$config->applog
		);

		$ldb = array();

		// expire OLP apps and collect app IDs by company
		foreach ($apps as $info)
		{
			$app->Update_Application_Status($info['application_id'], 'EXPIRED');
			$ldb[$info['company']][] = $info['application_id'];
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
