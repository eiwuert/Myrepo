<?php

/**
 * A collection of loan actions for an application VendorAPI State Data = ->loan_actions 
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 */
class VendorAPI_Blackbox_LoanActions implements Blackbox_StateData_ICombineKey
{
	/**
	 * Container array for loan actions
	 * @var array
	 */
	protected $loan_actions = array();
	
	/**
	 * Triggers that will be hit off of this
	 * loan action
	 *
	 * @var VendorAPI_Blackbox_Triggers
	 */
	protected $triggers;
	
	public function __construct(array $loan_actions = array(), VendorAPI_Blackbox_Triggers $triggers = NULL)
	{
		$this->loan_actions = $loan_actions;
		$this->triggers = $triggers;
	}

	/**
	 * Sets the trigger collection object?
	 *
	 * @param VendorAPI_Blackbox_Triggers $triggers
	 */
	public function setTriggers(VendorAPI_Blackbox_Triggers $triggers)
	{
		$this->triggers = $triggers;
	}
	
	/**
	 * Combines this loan actions list with another.
	 * @param VendorAPI_Blackbox_LoanActions $other The object to combine
	 * entries with.
	 * @return VendorAPI_Blackbox_LoanActions
	 */
	public function combine(Blackbox_StateData_ICombineKey $other = NULL)
	{
		if ($other)
		{
			if (!$other instanceof VendorAPI_Blackbox_LoanActions)
			{
				throw new Blackbox_Exception(sprintf(
					'%s cannot be combined with %s',
					get_class($this),
					get_class($other))
				);
			}

			foreach ($other->getLoanActions() as $loan_action)
			{
				$this->addLoanAction($loan_action);
			}
		}

		return $this;
	}

	/**
	 * Returns loan actions
	 *
	 * @return array
	 */
	public function getLoanActions()
	{
		return $this->loan_actions;
	}

	/**
	 * Adds a loan action to the loan actions
	 *
	 * @param string $loan_action
	 * @return void
	 */
	public function addLoanAction($loan_action)
	{
		//Loan actions are basically unique
		if (!in_array($loan_action, $this->loan_actions))
		{
			$this->loan_actions[] = $loan_action;
			$this->checkForTrigger($loan_action);
		}
	}
	
	/**
	 * Adds multiple loan actions to the list.
	 * @param array $actions string[] of loan action names
	 * @return void
	 */
	public function addLoanActions(array $actions)
	{
		$this->loan_actions = array_unique(array_merge($this->loan_actions, $actions));
	}
	
	/**
	 * Check all the triggers in our trigger
	 * collection to see which ones have 
	 * been hit.
	 *
	 * @param string $action
	 * @return void
	 */
	protected function checkForTrigger($action)
	{
		if ($this->triggers instanceof VendorAPI_Blackbox_Triggers)
		{
			foreach ($this->triggers as $trigger)
			{
				if (!strcasecmp($action, $trigger->getAction()))
				{
					$trigger->hit();	
				}
			}
		}
	}
	
	/**
	 * Return the triggers object or false
	 * if we have none
	 *
	 * @return VendorAPI_Blackbox_Triggers|Boolean
	 */
	public function getTriggers()
	{
		return ($this->triggers instanceof VendorAPI_Blackbox_Triggers) ? $this->triggers : FALSE;
	}
	
	/**
	 * Display a pretty string for this class.
	 *
	 * @return string Blank if no loans found.
	 */
	public function __toString()
	{
		return implode("\n", $this->loan_actions);
	}
}

?>
