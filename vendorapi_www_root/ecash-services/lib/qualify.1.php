<?php
// Version 1.0.0

// Moderately improved version of qualify that doesn't randomly start working on session data in the constructor.

class Qualify_1
{
	var $qualify_config;
	var $holiday_array;
	var $errors;
	
	function Qualify_1($qualify_config, $holiday_array)
	{
		$this->qualify_config = $qualify_config;
		$this->holiday_array = $holiday_array;
		$this->errors = array();
		//error_log("Qualify Instat ".$qualify_config."\n",3,"/tmp/qualify.log");
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

			case 'FOUR_WEEKLY':
				$monthly_net = floor($pay / 22 * 20);
			break;
			
			case 'MONTHLY':
				$monthly_net = $pay;
			break;
			
			default:
				$monthly_net = 0;
			break;
		}
		//error_log("Qualify Monthly Net: ".$monthly_net."\n",3,"/tmp/qualify.log");
		return $monthly_net;
	}
	
	function Job_Length($job_length)
	{
		// sometimes non-date 'trues' have made there way
		// to this point. If $job_length is not '1', 'TRUE', or
		// a date format (YYYY-MM-DD), we return 0 below.
		
		//error_log("Qualify Job Length: ".$job_length."\n",3,"/tmp/qualify.log");
		if (trim($job_length)=='1' || strtolower(trim($job_length))=='true') 
		{
			$job_length = date("Y-m-d", strtotime("-4 months"));
		}
		
		if( preg_match ('/^\d{4}-\d{1,2}-\d{1,2}$/', $job_length) )
		{
			list($y, $m, $d) = explode('-', $job_length);
			return ( strtotime("+3 months", mktime(0,0,0,$m,$d,$y)) < strtotime("now") ) ? 1: 0;			
		}
		//elseif (preg_match("/^false$/i", trim($job_length))) {
		// If not 1 or TRUE, return false
		else {
			return 0;
		}
	}
	
	function Calculate_Loan_Amount ($monthly_net, $direct_deposit, $pay_frequency, $income_source)
	{
		//echo "<pre>{$income_source} {$monthly_net}{$this->qualify_config->minimum_benefits_income}\n\n"; print_r($this->qualify_config); echo "</pre>"; die();
		//error_log("Qualify Calculate Loan Amt start ".$monthly_net."\n",3,"/tmp/qualify.log");
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
		
		if ($direct_deposit == "FALSE")
		{
			$loan_amt = $this->qualify_config->no_direct_deposit_amt;
		}
		else // Direct deposit.
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
		//error_log("Qualify loan amt: ".$loan_amt."\n",3,"/tmp/qualify.log");
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
		//error_log("Qualify Fund Date: ".$due_date."\n",3,"/tmp/qualify.log");
		
		return $due_date;
	}
	
	function Create_Paydates($income_frequency, $pay_date1, $pay_date2) 
	{
		//error_log("Qualify Create Paydates: ".$pay_date2."\n",3,"/tmp/qualify.log");
		
		switch ( $income_frequency )
		{
			case "WEEKLY":
				$pay_date3 = date("Y-m-d",strtotime("+1 week",strtotime($pay_date2)));
				$pay_date4 = date("Y-m-d",strtotime("+1 week",strtotime($pay_date3)));
			break;
			case "BI_WEEKLY":
				$pay_date3 = date("Y-m-d",strtotime("+2 weeks",strtotime($pay_date2)));
				$pay_date4 = date("Y-m-d",strtotime("+4 weeks",strtotime($pay_date2)));
			break;
			case "FOUR_WEEKLY":
				$pay_date3 = date("Y-m-d",strtotime("+4 weeks",strtotime($pay_date2)));
				$pay_date4 = date("Y-m-d",strtotime("+8 weeks",strtotime($pay_date2)));
			break;
			case "TWICE_MONTHLY":
				$pay_date3 = date("Y-m-d",strtotime("+1 month",strtotime($pay_date1)));
				$pay_date4 = date("Y-m-d",strtotime("+1 month",strtotime($pay_date2)));
			break;
			case "MONTHLY":
				$pay_date3 = date("Y-m-d",strtotime("+1 month",strtotime($pay_date2)));
				$pay_date4 = date("Y-m-d",strtotime("+1 month",strtotime($pay_date3)));
			break;
		}
		
		while( $this->_Is_Weekend(strtotime($pay_date3) ) || $this->_Is_Holiday(strtotime($pay_date3) ) )
		{
			$pay_date3 = date("Y-m-d",strtotime("+1 day",strtotime($pay_date3)));
		}
		
		while( $this->_Is_Weekend(strtotime($pay_date4) ) || $this->_Is_Holiday(strtotime($pay_date4) ) )
		{
			$pay_date4 = date("Y-m-d",strtotime("+1 day",strtotime($pay_date4)));
		}	
		
		return array("pay_date3" => $pay_date3, "pay_date4" => $pay_date4);
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
						
			if ($pay_date >= $due_date)
			{		
				$due_date = $pay_date;
				break;
			}	
		}
		
		if(!$direct_deposit || $direct_deposit == "FALSE" )
		{
			$due_date = strtotime("+1 day", $due_date);
		}
		
		
		//error_log("Qualify Loan Due Date: ".$due_date."\n",3,"/tmp/qualify.log");
	
		return $this->_Get_Next_Valid_Day($due_date);
	}
		
	// Payoff date and fund date need to be unix timestamps.
	function Calculate_Loan_Info($payoff_date, $fund_date, $loan_amount)
	{
		
		$days = round(($payoff_date - $fund_date) / 60 / 60 / 24 );
		
		if ($days < 1)
		{
			$days = 1;
		}
		
		$finance_charge = round($loan_amount / 50 * 15);
		
		$apr = round( (($finance_charge / $loan_amount / $days) * 365 * 100), 2);
		
		$total_payments = $loan_amount + $finance_charge;
		
		//error_log("Qualify Loan Info; ".$loan_amount.", ".$finance_charge."\n",3,"/tmp/qualify.log");
		
		return array("finance_charge" => $finance_charge, "apr" => $apr, "total_payments" => $total_payments);
	}
		
	function Qualify_Person($pay_dates, $pay_span, $pay, $direct_deposit, $job_length, $loan_amount = NULL)
	{


		$direct_deposit = ($direct_deposit == 'TRUE') ? 'TRUE' : 'FALSE';

		/*
			A little help:
			$pay_dates = array of dates as a string (not sure about format)
			$pay_span = upper case string of MONTHLY, WEEKLY, TWICE_MONTHLY, BI_WEEKLY, FOUR_WEEKLY
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
						
		if( $loan_amount == NULL && !$loan_amount = $this->Calculate_Loan_Amount ($pay, $direct_deposit, $pay_span, "BENEFITS") )
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
