<?php

/**
 * A decorator to run a rule contingent upon a date threshold
 *
 * This allows enterprises to run specific rules based on the amount of time
 * that has transpired since the react application was paid off.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_ReactVerification extends VendorAPI_Blackbox_Rule
{
	/**
	 * strtotime compatible time threshold (eg., '-45 days')
	 * @var string $threshold
	 */
	protected $threshold;

	/**
	 * @var ECash_API_2
	 */
	protected $api;

	/**
	 * @var ECash_Models_Application
	 */
	protected $application;

	/**
	 * @var Blackbox_IRule
	 */
	protected $rule;
	
	/**
	 * Variable that determines if the embedded rule was run
	 * @var bool
	 */
	protected $ran_embedded_rule = FALSE;

	/**
	 * @param string $threshold
	 * @param ECash_API_2 $api
	 * @param Blackbox_IRule $rule
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log, $threshold, eCash_API_2 $api, ECash_Models_Application $app, Blackbox_IRule $rule)
	{
		parent::__construct($log);
		$this->threshold = $threshold;
		$this->api = $api;
		$this->application = $app;
		$this->rule = $rule;
	}

	protected function getEventName()
	{
		return 'VERIFY_REACT';
	}

	/**
	 * Get a failure comment 
	 * @return string
	 */
	protected function failureComment()
	{
		return $this->failureShort() . ': failed with a threshhold of '
			. $this->threshold . ' for embedded rule class of '
			. get_class($this->rule);
	}

	/**
	 * Get a failure short
	 * @return string
	 */
	protected function failureShort()
	{
		return $this->getEventName();
	}

	
	/**
	 * @todo Make this the method underlying classes implement, instead of failureComment()/Short()
	 * @return null|VendorAPI_Blackbox_FailureReason
	 */
	protected function getFailureReason()
	{
		// If we ran the embedded rule, it should have set the failure reason.  Do
		// not return a failure reason
		if ($this->ran_embedded_rule)
		{
			return NULL;
		}

		return parent::getFailureReason();
	}
	
	/**
	 * Runs only if we're a react AND we have a react app ID, bank ABA, 
	 * and bank account
	 * @see lib/blackbox/Blackbox/Blackbox_StandardRule#canRun()
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		if ($this->isReact($data, $state))
		{
			$has_react_id = $state->customer_history->getReactID($state->name);

			return $has_react_id
				&& isset($data->bank_aba)
				&& isset($data->bank_account);
		}
		return FALSE;
	}

	/**
	 * Don't skip if it is a react
	 * @see code/VendorAPI/Blackbox/VendorAPI_Blackbox_Rule#onSkip()
	 * @return bool
	 */
	protected function onSkip(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		$this->setSkippable(!$this->isReact($data, $state));
		return parent::onSkip($data, $state);
	}

	/**
	 * If over the the threshold, runs the decorated rule
	 * @see lib/blackbox/Blackbox/Blackbox_Rule#runRule()
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		$react_app_id = $state->customer_history->getReactID($state->name);
		
		$loaded = $this->application->loadByKey($react_app_id);

		// Only continue the skip rule check logic if we loaded the react app
		if ($loaded)
		{
			$date = $this->api->Get_Status_Date(
				'paid',
				'paid::customer::*root',
				$react_app_id
			);
			// if we're not past the threshold and we haven't
			// changed our bank account info, we're good
			if (strtotime($date) > strtotime($this->threshold)
				&& $data->bank_aba == $this->application->bank_aba
				&& $data->bank_account == $this->application->bank_account)
			{
				$this->ran_embedded_rule = FALSE;
				return TRUE;
			}
		}
		$this->ran_embedded_rule = TRUE;
		return $this->rule->isValid($data, $state);
	}

	/**
	 * Is the current application a react
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state
	 * @return bool
	 */
	protected function isReact(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		// If we have an instance of ECash_CustomerHistory, use it to determine react status
		return isset($state->customer_history)
			&& $state->customer_history instanceof ECash_CustomerHistory
			&& $state->customer_history->getIsReact($state->name);
	}
}

?>
