<?php

/**
 * Performs daily calculation charges.
 *
 * Originally from http://gforge.sellingsource.com/svn/ecash/ecash_common/branches/recash_prep/code/ECash/Scheduling/DailyInterestCalculator.php
 * 
 * @author Justin Foell <justin.foell@sellingsource.com>
 * @author Mike Lively <mike.lively@sellingsource.com>
 * @package Transactions
 */
class ECash_Transactions_DailyRateCalculator extends ECash_Transactions_RateCalculator
{
	protected $accrual_limit;
    protected $countdown_date;

	/**
	 * @param int $countdown_date Unix Timestamp
	 */
	public function __construct(ECash_Transactions_Rounder $rounder, $percent, $accrual_limit = NULL, $countdown_date = NULL)
	{
		parent::__construct($rounder, $percent);
		$this->accrual_limit = $accrual_limit;
		$this->countdown_date = $countdown_date;
	}

	/**
	 * Returns the calculated charge for the given time span on the given 
	 * amount.
	 *
	 * Originally from Interest_Calculator::calculateDailyInterest()
	 *
	 * @todo add checking for non-optional from_date/to_date
	 * @param float $amount
	 * @param int $from_date Unix Timestamp
	 * @param int $to_date Unix Timestamp
	 * @param int $countdown_date Unix Timestamp
	 */
	public function calculateCharge($amount, $from_date = NULL, $to_date = NULL)
	{
		if(!Date_Util_1::isTimestamp($from_date))
			$from_date = strtotime($from_date);
		if(!Date_Util_1::isTimestamp($to_date))
			$to_date = strtotime($to_date);

		if($to_date < $from_date)
		{
			$days = 0;
		}
		else
		{
			$days = Date_Util_1::dateDiff($from_date, $to_date);
		}

		/**
		 * We are assuming that if the value of
		 * service_charge_accrual_limit evaluates to false that the
		 * accrual limit either isn't set, or the business rule is
		 * intentionally set to 0, which means that no accrual
		 * limitations are to be used.
		 */
		if($this->accrual_limit)
		{
			//If there's a countdown date, we'll use that to check and see if the accrual limit has been reached.
			if($this->countdown_date)
			{
				if (Date_Util_1::dateDiff($this->countdown_date, $to_date) > $this->accrual_limit) 
				{
					//[#53997] if countdown_date to from_date > accrual_limit, just use accrual limit (instead of a negative value)
					$days = Date_Util_1::dateDiff($this->countdown_date, $from_date);
					if(($calc_days = $this->accrual_limit - $days) > 0)
					{
						$days = $calc_days;
					}
					else
					{
						//#55355 should stop accrualling interest at this point
						$days = 0;
					}
				}
			}
			else 
			{
				$days = ($days > $this->accrual_limit) ? $this->accrual_limit : $days;
			}
		}
		
		$daily_rate = (( $this->percent / 100) / 7);
		$service_charge = (($daily_rate * $amount) * $days);
		return $this->round($service_charge);
	}

	/**
	 * Returns the description for how the charge would be calculated.
	 *
	 * @todo add checking for non-optional from_date/to_date
	 * @param float $amount
	 * @param int $from_date Unix Timestamp
	 * @param int $to_date Unix Timestamp
	 * @return string
	 */
	public function getDescription($amount, $from_date = NULL, $to_date = NULL)
	{
		$days = Date_Util_1::dateDiff($from_date, $to_date);
		return "{$this->percent}% from " . date('m/d/Y', $from_date) . ' to ' . date('m/d/Y', $to_date);
	}
	
	/**
	 * Returns the initial assessment to charge at the onset of the loan.
	 *
	 * @param float $amount
	 */
	public function getInitialCharge($amount)
	{
		return 0;
	}


	/**
	 * Returns the APR.
	 *
	 * @param int $date_fund_actual Unix Timestamp -- not used, pass NULL
	 * @param int $date_first_payment Unix Timestamp -- not used, pass NULL
	 * @return float
	 */
	public function getAPR($date_fund_actual = NULL, $date_first_payment = NULL)
	{
		//@TODO not sure if the same rounder is supposed to be used for APR
		return $this->round(($this->percent / 7) * 365);
	}

	/**
	 * @TODO replace calls to scheduleCalculateInterest() with the approprite schedule call -- used to look like this:
	 *	
	 * $paid_to = self::getInterestPaidPrincipalAndDate($schedule, $include_scheduled);
	 * $service_charge = self::calculateDailyInterest($rules, $paid_to['principal'], $paid_to['date'], $end_date, $paid_to['first_failure_date']);
	 */

	/**
	 * @TODO replace calls to getInterestPaidPrincipalAndDate with the appropriate schedule calls
	 */	
}

?>
