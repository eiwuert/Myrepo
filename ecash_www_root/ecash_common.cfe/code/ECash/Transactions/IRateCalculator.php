<?php

  /**
   * Originally from http://gforge.sellingsource.com/svn/ecash/ecash_common/branches/recash_prep/code/ECash/Scheduling/IInterestCalculator.php
   */
interface ECash_Transactions_IRateCalculator
{	
	/**
	 * Returns the calculated charge for the given time span on the given 
	 * amount.
	 *
	 * @param float $amount
	 * @return 
	 */
	public function calculateCharge($amount, $from_date = NULL, $to_date = NULL);
	
	/**
	 * Returns the description for how the charge would be calculated.
	 *
	 * @param float $amount
	 * @param int $from_date Unix Timestamp
	 * @param int $to_date Unix Timestamp
	 * @return string
	 */
	public function getDescription($amount, $from_date = NULL, $to_date = NULL);
	
	/**
	 * Returns the initial assessment to charge at the onset of the loan.
	 *
	 * @param float $amount
	 */
	public function getInitialCharge($amount);

	public function getAPR($date_fund_actual = NULL, $date_first_payment = NULL);

	public function round($amount);

	public function getPercent();
}

?>