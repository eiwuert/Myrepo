<?php

/**
 * Methods for calculating Interest Rates
 */
class Interest_Calculator
{
	/**
	 * Returns the daily interest amount based on the amount
	 * and the number of days passed in using the rate in the 
	 * rule set.
	 *
	 * @param array $rules
	 * @param int $amount
	 * @param string $first_date
	 * @param string $last_date
	 * @return float
	 */

	static public function calculateDailyInterest($rules, $amount, $first_date, $last_date, $failure_date=NULL,$countdown_date=NULL)
	{ 
		$svc_charge_percentage = $rules['svc_charge_percentage']?$rules['svc_charge_percentage']:$rules['service_charge']['svc_charge_percentage'];
		// Determine daily rate

		if(strtotime($failure_date)>strtotime($first_date))
		{
			$first_date = $failure_date;
		}
		
		$days = self::dateDiff($first_date, $last_date);
		if(strtotime($last_date) < strtotime($first_date))
		{
			$days = 0;
		}
		
		// Sometimes the accrual limit is set in $rules['interest_accrual_limit'], we need to account for this possibility
		$accrual_limit = isset($rules['service_charge']['interest_accrual_limit'])?$rules['service_charge']['interest_accrual_limit']:$rules['interest_accrual_limit'];

		// We are assuming that if the value of $accrual_limit evaluates to false that the accrual limit either
		// isn't set, or the business rule is intentionally set to 0, which means that no accrual limitations are to 
		// be used.
		if($accrual_limit)
		{
			//If there's a countdown date, we'll use that to check and see if the accrual limit has been reached.
			if($countdown_date)
			{
				if (self::dateDiff($countdown_date,$last_date)>$accrual_limit) 
				{
					$days = $accrual_limit - self::dateDiff($countdown_date,$first_date);
				}
			}
			else 
			{
				$days = ($days > $accrual_limit) ? $accrual_limit : $days;
			}
		}

		if($days <= 0 )
		{
			return number_format(0, 2);
		}


		
		if(strtolower($rules['service_charge']['svc_charge_type']) == 'fixed')
		{
			$daily_rate = ( $svc_charge_percentage / 100);
			$service_charge = $daily_rate * $amount;
		}
		else
		{
			$daily_rate = (( $svc_charge_percentage / 100) / 7);
			$service_charge = (($daily_rate * $amount) * $days);
		}
		$interest = self::roundInterest($service_charge,$rules['interest_rounding']['type'],$rules['interest_rounding']['digit']);
		return $interest;
	}
	
	/**
	 * roundInterest Rounds the amount for the interest based on the parameters passed.
	 * This function is to accomodate the different rules for rounding that different companies have
	 *
	 * @param float $charge the interest amount to round
	 * @param string $round_type the type of rounding to perform on the charge.
	 * @param int $decimal_place the amount of decimal precision to use when rounding the charge.
	 * @return float the rounded interest amount.
	 */
	static public function roundInterest($charge, $round_type = 'default', $decimal_place = 2)
	{
		$interest = 0;
		
		//If the charge is 0, there's nothing to round, let's return a formatted value of 0.00 right now!
		if ($charge == 0) 
		{
			return number_format($charge, 2, '.', '');
		}
		
		switch ($round_type)
		{
			//Truncates the value/ Rounds down
			case 'none':
				$interest = self::roundDown($charge, $decimal_place);
			break;
			
			//Bankers' rounding 
			case 'banker':
				$interest = self::bankersRound($charge, $decimal_place);
			break;	
			
			//Always rounds the value up
			case 'up':
				$interest = self::roundUp($charge, $decimal_place);
			break;

			//Uses standard rounding
			case 'default':
			default:
				$interest = round($charge, $decimal_place);	
			break;
		}
		
		return number_format($interest, 2, '.', '');
	}
	/**
	 * roundDown
	 * Always rounds the digit down/truncates the value
	 *
	 * @param float $charge the unrounded interest charge
	 * @param int $decimal_place the decimal precision to round to
	 * @return float the rounded interest charge
	 */
	static public function roundDown($charge, $decimal_place)
	{
		//Note:  If we start dealing with some sort of foreign formatting, we may have to re-evaluate using the decimal
		//place to facilitate the truncating.
		//Alright, screw this rounding thing!  We're just going to turn the number into a string and truncate it!
		$charge = trim($charge);
		//turn the charge into an array.
	    $number = explode(".",$charge);
		//Return the orignal to the desired precision or less.
		//changed the number_format call to return nothing as a thousands separator so it can still be parsed as a number [jeffd][#13904]
	    return number_format($number[0] . "." . substr($number[1],0,$decimal_place), $decimal_place, '.','');
	}
	
	/**
	 * roundUp
	 * Always rounds the digit up, disregarding standard rounding rules.
	 *
	 * @param float $charge the unrounded interest charge
	 * @param int $decimal_place the decimal precision to round to
	 * @return float the rounded interest charge
	 */
	static public function roundUp($charge, $decimal_place)
	{
		//changed the number_format call to return nothing as a thousands separator so it can still be parsed as a number [jeffd][#13904]
		return number_format(($charge+(5*pow(10,-$decimal_place-1))),$decimal_place,'.','');	
	}
	
	/**
	 * bankersRound
	 * Performs bankers' rounding on Interest charge.
	 * Bankers rounding is identical to the common method of rounding except when the digit(s) 
	 * following the rounding digit start with a five and have no non-zero digits after it. The new algorithm is:
     * -- Decide which is the last digit to keep.
     * -- Increase it by 1 if the next digit is 6 or more, or a 5 followed by one or more non-zero digits.
     *  -- Leave it the same if the next digit is 4 or less
	 *  --Otherwise, all that follows the last digit is a 5 and possibly trailing zeroes; 
     * 		then change the last digit to the nearest even digit. 
     * 		That is, increase the rounded digit if it is currently odd; leave it if it is already even.
	 *
	 * @param float $charge the raw, unrounded interest charge
	 * @param int $decimal_place the amount of decimals places you want to use when rounding
	 * @return float the Bankers' rounded interest charge.
	 */
	static public function bankersRound ($charge,$decimal_place)
	{
		$format_str = "%01." . ($decimal_place + 1) . "f";
	    $money_str = sprintf($format_str, self::roundUp($charge, ($decimal_place + 1))); 
	    $last_pos = strlen($money_str)-1;   
	    if ($decimal_place == 0)
	    {
	    	$second_last_pos = strlen($money_str)-3; 
	    }
	    else 
	    {
	    	$second_last_pos = strlen($money_str)-2;                     
	    }
	    
	    if ($money_str[$last_pos] === "5")
	    {
	    	$money_str[$last_pos] = ((int)$money_str[$second_last_pos] & 1) ? "9" : "0"; 
	    }
	    return round($money_str, $decimal_place); 
	}
	
	/**
	 * Loops through the schedule and determines the last date and principal amount when interest was paid up
	 * This is useful for calculations of further interest.
	 * returns associative array with keys principal and date
	 */
	static public function getInterestPaidPrincipalAndDate($schedule, $include_scheduled = FALSE, $rules = NULL, $include_reattempts = TRUE ) 
	{
		$original_failure_date = NULL;
		$delinquency_date = NULL;
		$original_failures = array();
		$first_failure_date = NULL;
		$failure_count = 0;
		$max_failures = (isset($rules['max_svc_charge_failures'])) ? $rules['max_svc_charge_failures'] : 2;
		$last_completed_date = self::getLastCompletedTransaction($schedule);
		
		$principal_balance = 0;
		$last_date = Date('Y-m-d');
		
		foreach($schedule as $e)
		{
			// Skip scheduled items unless otherwise directed
			if($e->status === 'scheduled'  && $include_scheduled === FALSE)
			{
				continue;
			}
			
			// Ignore failed items.
			if($e->status === 'failed' )
			{ 
				$failure_count++;
				if (!$original_failure_date)
				{
					$original_failure_date = $e->date_effective;
				}
				//echo "transaction id = {$e->transaction_register_id}<br>";
				if (!$delinquency_date)
				{							
					if($e->is_fatal == 'yes')
					{
						$delinquency_date = $e->date_effective;
					}
								
					if (array_key_exists($e->origin_id,$original_failures))
					{
						$delinquency_date = $original_failures[$e->origin_id];
					}
					
					$original_failures[$e->transaction_register_id] = $e->date_effective;
					
					if ($failure_count > $max_failures) 
					{
						$delinquency_date = $original_failure_date;
					}
				}
				if(strtotime($e->date_effective) > strtotime($last_completed_date) && !$first_failure_date)
				{
					//first_failure_date refers to the first failure after all 'complete' transactions
					$first_failure_date = $e->date_effective;
				}
				continue;
			}
			
			// Tally up the amounts for Principal and Service Charge (Interest)
			//*note - do this first so you have proper values when you compute interest
			foreach($e->amounts as $ea)
			{
				if($ea->event_amount_type === 'principal' && isset($ea->amount) && $ea->amount <> 0)
				{
					$principal_balance += $ea->amount;
					if($include_reattempts || $e->context != 'reattempt')
						$last_date = $e->date_effective;
				}
				elseif($ea->event_amount_type === 'service_charge' && $ea->amount <> 0)
				{
					if($include_reattempts || $e->context != 'reattempt')
						$last_date = $e->date_effective;
				}

			}
			
		}
		if (!$delinquency_date)
		{
			$delinquency_date = $last_date;
		}
		
		return Array('principal' => $principal_balance, 'date' => $last_date, 'first_failure_date' => $first_failure_date, 'delinquency_date' => $delinquency_date);
	}

	/**
	 * Determines how much interest is owed up to the point of date_effective.  
	 * If date_effective is not specified, the next business day is used. 
	 * If include_scheduled is TRUE then any scheduled events will be 
	 * included when determining the final amount owed.
	 *
	 * @param array $rules
	 * @param array $schedule
	 * @param string $end_date
	 * @param bool $include_scheduled
	 * @return float
	 */
	static public function scheduleCalculateInterest($rules, $schedule, $end_date = NULL, $include_scheduled = FALSE, $include_reattempts = TRUE )
	{
		if(! is_array($rules) || ! is_array($schedule)) return NULL;

		$principal_balance = 0;
		$interest_balance  = 0;
		$last_date = NULL;
		
		if($end_date === NULL) 
		{ 
			$end_date = date('Y-m-d');
		}

		$paid_to = self::getInterestPaidPrincipalAndDate($schedule, $include_scheduled, $rules, $include_reattempts);

		$service_charge = self::calculateDailyInterest($rules, $paid_to['principal'], $paid_to['date'], $end_date, $paid_to['first_failure_date'], $paid_to['delinquency_date']);
		return number_format($service_charge, 2, '.', '');
	}
	
	/**
	 * Calculates the difference between two dates.  The lesser date
	 * is always subtracted from the greater.  Parameters must be a
	 * valid string for strtotime.  This is duplicated in ecash3.0/lib/pay_date_calc.3.php
	 *
	 * @param string $date_a
	 * @param string $date_b
	 */
	static public function dateDiff($date_a, $date_b)
	{
		$a = strtotime($date_a);
		$b = strtotime($date_b);
		
		if($a <= 0 || $b <= 0)
		{
			throw new Exception("Invalid Date passed to " . __METHOD__ );
		}
		$val = abs($a - $b) / 86400;
		return round(abs($a - $b) / 86400, 0);
	}
	
	/**
	 * Finds the last completed transaction 
	 *
	 * @param object $schedule
	 */
	static public function getLastCompletedTransaction($schedule)
	{
		$lastComplete = NULL;
		foreach($schedule as $e)
		{
			if(!$lastComplete)
			{
				$lastComplete = $e->date_effective;
			}
			if($e->status == 'complete' && in_array($e->clearing_type, array('ach','external')))
			{
				$lastComplete = $e->date_effective;
			}
		}
		return $lastComplete;
	}
	
}
