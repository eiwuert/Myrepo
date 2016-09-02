<?php

// Moderately improved version of qualify that doesn't randomly start working on session data in the constructor.

class Qualify
{
	var $sql;
	var $data;
	var $qualify_config;
	var $holiday_array;
	var $errors;
	
	function Qualify($qualify_config, $holiday_array)
	{
		$this->qualify_config = $qualify_config;
		$this->holiday_array = $holiday_array;
		$this->errors = array();
	}
	
	function Calculate_Monthly_Net($pay_span, $pay)
	{
		switch ($pay_span)
		{
			case 'WEEKLY':
				$monthly_net = $pay / 4;
			break;
			
			case 'BI_WEEKLY':
			case "TWICE_MONTHLY":
				$monthly_net = $pay / 2;
			break;
					
			case 'MONTHLY':
				$monthly_net = $pay;
			break;
			
			default:
				$monthly_net = 0;
			break;
		}
		return $monthly_net;
	}
	
	function Job_Length($job_length)
	{
		if( preg_match ('/^\d{4}-\d{1,2}-\d{1,2}$/', $job_length) )
		{
			return ( strtotime("+3 months", $job_length) < strtotime("now") ) ? 1: 0;			
		}
		return ($job_length) ? 1: 0;
	}
	
	function Calculate_Loan_Amount ($monthly_net, $direct_deposit, $pay_frequency, $income_source)
	{
		//echo "<pre>{$income_source} {$monthly_net}{$this->qualify_config->minimum_benefits_income}\n\n"; print_r($this->qualify_config); echo "</pre>"; die();
		
		switch ($income_source)
		{
			case "BENEFITS":
				if ($monthly_net < $this->qualify_config->minimum_benefits_income )
				{
					$this->errors[] = "You must make at least \$800 monthly in benefits income for a loan";
					return FALSE;
				}
			break;
			
			case "EMPLOYMENT":
				if ($monthly_net< $this->qualify_config->minimum_job_income )
				{
					$this->errors[] = "You must make at least \$1000 monthly in job income for a loan";
					return FALSE;
				}
			break;
		}
		
		if ($direct_deposit == "FALSE" || $pay_frequency == "MONTHLY")
		{
			$loan_amt = $this->qualify_config->no_direct_deposit_amt;
		}
		else // Direct deposit and not paid monthly.
		{
			// i dont like how this part was written, but because of time i will leave it for now
			ksort($this->qualify_config->amounts);
			foreach( $this->qualify_config->amounts as $income=>$amount )
			{
				if( !isset($loan_amt) && $monthly_net < $income )
				{
					$loan_amt = $amount;
				}
			}
			// if loan amount higher than greatest amount in array set the last value
			if( !isset($loan_amt) ) 
			{
				$loan_amt = $amount;
			}
		}
		return $loan_amt;
	}
	
	function Estimate_Fund_Date ()
	{
		// _Get_Today
		// due_date = Today + 1 days
		$due_date = strtotime("+1 day", mktime(0, 0, 0, date("m"), date("d"), date("Y")));//$this->_Get_Date ($this->_today, 0, 0, 1);
				
		// _Is_Weekend?
		// _Is_Holiday?
		$due_date = $this->_Get_Next_Valid_Day ($due_date);
		
		// return Day
		return $due_date;
	}
	
	function _Get_Next_Valid_Day ($day)
	{
		while ($this->_Is_Weekend ($day) || $this->_Is_Holiday ($day))
		{
			$day = strtotime("+1 day", $day);
		}
		return $day;
	}
	
	function _Is_Holiday($date)
	{
		if (in_array (date ("Y-m-d", $date), $this->holiday_array))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	function _Is_Weekend($date)
	{
		if (date ("w", $date) == 0 || date ("w", $date) == 6)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	function Loan_Due_Date ($fund_date, $pay_date_array, $direct_deposit)
	{
		$due_date = strtotime ("+10 days", $fund_date);
		
		foreach($pay_date_array as $pay_date)
		{
			$pay_date = strtotime($pay_date);
						
			if ($pay_date > $due_date)
			{		
				$due_date = $pay_date;
				break;
			}	
		}
			
		if(!$direct_deposit)
		{
			$due_date = strtotime("+1 day", $due_date);
		}
		
		return $this->_Get_Next_Valid_Day($due_date);
	}
		
	// Payoff date and fund date need to be unix timestamps.
	function Calculate_Loan_Info($payoff_date, $fund_date, $loan_amount)
	{
		$days = round( ($payoff_date - $fund_date) / 60 / 60 / 24 );
		
		$finance_charge = round($loan_amount / 50 * 15);
		
		$apr = round( (($finance_charge / $loan_amount / $days) * 365 * 100), 2);
		
		$total_payments = $loan_amount + $finance_charge;
		
		return array("finance_charge" => $finance_charge, "apr" => $apr, "total_payments" => $total_payments);
	}
		
	function Qualify_Person($pay_dates, $pay_span, $pay, $direct_deposit, $job_length)
	{
		/*
			A little help:
			$pay_dates = array of dates as a string (not sure about format)
			$pay_span = upper case string of MONTHLY, WEEKLY, TWICE_MONTHLY, BI_WEEKLY
			$pay = integer representing monthly income
			$direct_deposit = upper case string of TRUE, FALSE
			$job_length = date formated as YYYY-MM-DD
		*/
		if( !$monthly_net = $this->Calculate_Monthly_Net($pay_span, $pay) )
		{
			$this->errors[] = "Invalid pay span, or monthly net pay is zero.";
			return array('errors' => $this->errors);
		}
		
		if( !$this->Job_Length($job_length) )
		{
			$this->errors[] = "Not enough time at this job.";
			return array('errors' => $this->errors);
		}
		else
		{
			$job_length = "3";
		}
						
		if( !$loan_amount = $this->Calculate_Loan_Amount ($pay, $direct_deposit, $pay_span, "BENEFITS") )
		{
			return array('errors' => $this->errors);
		}
		
		// Everything below uses magic numbers, and I have no idea how they were derived.
		
		$finance_charge = round($loan_amount / 50 * 15);
		
		$fund_date = $this->Estimate_Fund_Date();

		$payoff_date = $this->Loan_Due_Date($fund_date, $pay_dates, $direct_deposit);
		
		$days = round( ($payoff_date - $fund_date) / 60 / 60 / 24 );
		
		$apr = round( (($finance_charge / $loan_amount / $days) * 365 * 100), 2);
		
		$total_payments = $loan_amount + $finance_charge;
		
		$payoff_date = date("Y-m-d", $payoff_date);
		
		$fund_date = date("Y-m-d", $fund_date);
		
		return array('finance_charge' => $finance_charge, 'fund_date' => $fund_date, 'payoff_date' => $payoff_date, 'apr' => $apr,
				 'total_payments' => $total_payments, 'fund_amount' => $loan_amount, 'net_pay' => $monthly_net, 'job_length' => $job_length);
	}
}

?>
