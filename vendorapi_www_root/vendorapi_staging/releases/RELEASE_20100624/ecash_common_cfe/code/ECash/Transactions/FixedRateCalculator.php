<?php

/**
 * Performs fixed rate calculations.
 *
 * Originally from http://gforge.sellingsource.com/svn/ecash/ecash_common/branches/recash_prep/code/ECash/Scheduling/FixedInterestCalculator.php
 *
 * @author Justin Foell <justin.foell@sellingsource.com>
 * @author Mike Lively <mike.lively@sellingsource.com>
 * @package Transactions
 */
class ECash_Transactions_FixedRateCalculator extends ECash_Transactions_RateCalculator
{
	
	/**
	 * Returns the calculated charge for the given time span on the given 
	 * amount.
	 *
	 * @param float $amount
	 * @param int $from_date Unix Timestamp -- not used, pass NULL
	 * @param int $to_date Unix Timestamp -- not used, pass NULL
	 * @return void
	 */
	public function calculateCharge($amount, $from_date = null, $to_date = null)
	{
		$service_charge = ($this->percent / 100) * $amount;
		return $this->round($service_charge);
	}

	/**
	 * Returns the description for how the charge would be calculated.
	 *
	 * @param float $amount
	 * @param int $from_date Unix Timestamp -- not used, pass NULL
	 * @param int $to_date Unix Timestamp -- not used, pass NULL
	 * @return string
	 */
	public function getDescription($amount, $from_date = null, $to_date = null)
	{
		return "Fixed {$this->percent}% Charge";
	}
	
	/**
	 * Returns the initial assessment to charge at the onset of the loan.
	 *
	 * @param float $amount
	 */
	public function getInitialCharge($amount)
	{
		return $this->calculateCharge($amount);
	}

	/**
	 * Returns the APR.  Originally Agean_eCash_API_2 getAPR()
	 *
	 * @param int $date_fund_actual Unix Timestamp
	 * @param int $date_first_payment Unix Timestamp
	 * @return float
	 */
	public function getAPR($date_fund_actual = NULL, $date_first_payment = NULL)
	{
		if($date_fund_actual && $date_first_payment)
		{
			$num_days = Date_Util_1::dateDiff($date_fund_actual, $date_first_payment);
			$num_days = ($num_days < 1) ? 1 : $num_days;

			//@TODO not sure if the same rounder is supposed to be used for APR
			return $this->round(($this->percent / $num_days) * 365);
		}
		throw new Exception (__METHOD__ . ": Fixed interest applications require starting and ending timestamps for the relevant time period.");
	}
	
}

?>