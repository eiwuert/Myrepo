<?php

/**
 * The winner object for enterprise companies
 * This includes additional information, such as whether the application
 * was determined to be a react.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Winner extends Blackbox_Winner
{
	/**
	 * @var ECash_CustomerHistory
	 */
	protected $history;

	/**
	 * @param Blackbox_ITarget $target the target who won
	 * @param ECash_CustomerHistory $history the customer history of the target
	 */
	public function __construct(VendorAPI_Blackbox_Target $target, ECash_CustomerHistory $history)
	{
		$this->target = $target;
		$this->history = $history;
	}

	/**
	 * Gets the customer history from the previous customer checks
	 *
	 * @return ECash_CustomerHistory
	 */
	public function getCustomerHistory()
	{
		return $this->history;
	}

	/**
	 * Returns whether the app was determined to be react
	 *
	 * @return bool
	 */
	public function getIsReact()
	{
		$state = $this->target->getStateData();
		return $this->history->getIsReact($state->name);
	}
	
	/**
	 * Returns the largest react application ID
	 *
	 * @return int
	 */
	public function getReactID()
	{
		$state = $this->target->getStateData();
		return $this->history->getReactID($state->name);
	}

	/**
	 * Returns the companies with DNL flags
	 *
	 * @return array
	 */
	public function getDoNotLoan()
	{
		return $this->history->getDoNotLoan();
	}

	/**
	 * Returns the companies with DNL overrides
	 *
	 * @return array
	 */
	public function getDoNotLoanOverride()
	{
		return $this->history->getDoNotLoanOverride();
	}
	
	/**
	 * Return an array of loan actions from
	 * the state data
	 *
	 * @return array
	 */
	public function getLoanActions()
	{
		$la = $this->getStateData()->loan_actions;
		return $la instanceof VendorAPI_Blackbox_LoanActions ? $la->getLoanActions() : array();
	}
	
	/**
	 * Return the triggers back out of the winner?
	 *
	 * @return array
	 */
	public function getTriggers()
	{
		$la = $this->getStateData()->loan_actions;
		if ($la instanceof VendorAPI_Blackbox_LoanActions)
		{
			$triggers = $la->getTriggers();
			if ($triggers instanceof VendorAPI_Blackbox_Triggers)
			{
				$return = new VendorAPI_Blackbox_Triggers();
				foreach ($triggers as $trigger)
				{
					if ($trigger->isHit())
					{
						$return->addTrigger($trigger);	
					}
				}
				return $return;
			}
			return FALSE;
		}
		return array();
	}
}

?>
