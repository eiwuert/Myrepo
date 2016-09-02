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
 * @package VendorAPI
 * @subpackage PreviousCustomer
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_PreviousCustomerCollection extends Blackbox_RuleCollection 
{
	/**
	 * @var array
	 */
	protected $rules = array();

	/**
	 * @var bool
	 */
	protected $expire_apps = FALSE;

	/**
	 * @param bool $expire_apps
	 * @return void
	 */
	public function __construct($expire_apps = FALSE)
	{
		$this->expire_apps = $expire_apps;
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
	 * @param Blackbox_IRule $rule
	 * @return void
	 */
	public function addRule(Blackbox_IRule $rule)
	{
		if (!$rule instanceof VendorAPI_Blackbox_Rule_PreviousCustomer)
		{
			throw new Blackbox_Exception('Rule must be an instance of VendorAPI_Blackbox_Rule_PreviousCustomer');
		}

		/* @var $rule VendorAPI_Blackbox_Rule_PreviousCustomer */
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
			|| !($state->customer_history instanceof ECash_CustomerHistory))
		{
			throw new Blackbox_Exception(sprintf(
				'Customer history was not found, got %s',
				var_export($state->customer_history, TRUE))
			);
		}

		$valid = TRUE;

		/* @var $rule VendorAPI_Blackbox_Rule_PreviousCustomer */
		foreach ($this->rules as $rule)
		{
			$valid = $rule->isValid($data, $state);

			// record results on a per-check basis...
			// these are used later to populate session values
			if ($rule_result = $rule->getResult())
			{
				$state->customer_history->setResult(
					$rule->getName(),
					$rule_result->getDecision()
				);
			}

			if ($valid === FALSE) break;
		}

		return $valid;
	}
}

?>
