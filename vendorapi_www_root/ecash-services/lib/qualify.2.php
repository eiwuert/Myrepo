<?php
require_once("business_rules.class.php");
require_once("ecash_api.php");

/**
 * Qualify performns calculations for income validation and loan estimates.
 *
 * @author Ray Lopez <raymond.lopez@sellingsource.com>
 */
class Qualify_2
{
	public $config;
	public $holiday_array;
	public $errors;

	protected $loan_type_id;
	private $sql;
	protected $ldb;
	private $app_log;
	private $use_old_config;
	private $is_wap;
	
	/**
	 * The loan type name to use
	 *
	 * @var unknown_type
	 */
	protected $loan_type_name = 'standard';
	
	/**
	 * @var bool
	 */
	protected $is_ecash_react;
	
	/**
	 * grace period in seconds
	 *
	 * @var int
	 */
	protected $grace_period;

	/**
		@publicsection
		@public
		@brief
			Qualify_2

    	@param $config string Winner/Config Object
    	@param $holiday_array array Holiday Array
    	@param $sql object OLP MySQL4 SQL
    	@param $ldb object LDB MySQLi SQL
    	@param $applog object App Logg
    	@return $config object Config Object
    */

	public function Qualify_2($prop, $holiday_array = NULL, $sql = NULL, $ldb = NULL, $applog = NULL, $mode = NULL)
	{

		if (is_object($applog))
		{
			$this->app_log = &$applog;
		}


		// KEPT FOR COMPATIBILITY:
		if (is_object($prop))
		{
			$this->prop = $prop;
		}

		elseif(is_string($prop) && !empty($prop) && is_object($sql))
		{
			$prop = strtolower($prop);

			$this->sql = &$sql;
			$this->ldb = &$ldb;
			if(is_null($ldb) && !is_null($mode))
			{
				$this->ldb = &Setup_DB::Get_Instance('mysql', $mode . '_READONLY', $prop);
			}

			// get  config from business rule db
			$this->use_old_config = FALSE;
			$this->Get_Rule_Config($prop);
		}

		if (is_array($holiday_array))
		{
			$this->holiday_array = $holiday_array;
		}
		else
		{
			$this->holiday_array = array();
		}

		$this->errors = array();
		$this->is_ecash_react = FALSE;

	}
	
	/** Add ability to tell Qualify to run eCash-specific business rules.
	 * Created for GForge #9013 by Ryan Murphy.
	 *
	 * @param bool $is_ecash_react If running as eCash react.
	 * @return bool The current value of the flag.
	 */
	public function setIsEcashReact($is_ecash_react = NULL)
	{
		if (isset($is_ecash_react))
		{
			$this->is_ecash_react = (bool)$is_ecash_react;
		}
		
		return $this->is_ecash_react;
	}

	// Mantis #11748 - function to set the is_wap flag. I dub thee Weapp (WAP React)  [RV]
	public function Set_Is_Weapp($is_wap)
	{
		$this->is_wap = $is_wap;
	}

    /**
		@publicsection
		@public
		@brief
			Calculate From Monthly Net

    	@param $pay_span array Payment Span
    	@param $pay string Payment
    	@return $monthly_net array Monthly Span, FALSE on failure
    */
	public function Calculate_Monthly_Net($pay_span, $pay)
	{

		$paycheck = FALSE;

		switch (strtoupper($pay_span))
		{

			case 'WEEKLY':
                $paycheck = round(($pay * 12) / 52);
                break;
            case 'BI_WEEKLY':
                $paycheck = round(($pay * 12) / 26);
                break;
            case 'FOUR_WEEKLY':
                $paycheck = round(($pay * 12) / 13);
                break;
            case 'TWICE_MONTHLY':
                $paycheck = round($pay / 2);
                break;
            case 'MONTHLY':
                $paycheck = round($pay);
                break;
			default:
				$this->errors[] = "Invalid pay span, or monthly net pay is zero.";
		}

		return $paycheck;

	}

    /**
		@publicsection
		@public
		@brief
			Check Job Length

    	@param $job_length string Job Length
    	@return $valid bool Valid Income
    */
	public function Check_Job_Length($job_length)
	{

		//error_log("Qualify Job Length: ".$job_length."\n",3,"/tmp/qualify.log");

		$valid = FALSE;

		if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $job_length))
		{

			$date_hire = strtotime($job_length);
			$valid = ($date_hire > strtotime('-3 Months'));

		}
		elseif (trim($job_length)=='1' || strtolower(trim($job_length))=='true')
		{

			$valid = TRUE;

		}
		else
		{

			$this->errors[] = 'Not enough time at this job.';
			$valid = FALSE;

		}

		return($valid);

	}

    /**
		@publicsection
		@public
		@brief
			Check_Income

    	@param $income_source string Income SOurce
    	@param $monthly_net string Monthly Net
    	@return $valid bool Valid Income
    */
	public function Check_Income($income_source, $monthly_net)
	{

		$valid = FALSE;
		$min_income = NULL;

		switch (strtoupper($income_source))
		{

			case 'BENEFITS':
				$income_source = 'benefits';
				$min_income = $this->config->minimum_benefits_income;
				break;

			case 'EMPLOYMENT':
				$income_source = 'job';
				$min_income = $this->config->minimum_job_income;
				break;

		}

		if (is_numeric($min_income) && ($monthly_net > $min_income))
		{
			$valid = TRUE;
		}
		else
		{

			$this->errors[] = "You must make at least \${$min_income} monthly in {$income_source} income for a loan";
			$valid = FALSE;

		}

		return($valid);

	}

    /**
		@publicsection
		@public
		@brief
			Calculate React Loan Amount (Calculate Loan Amount wrapper)
	* Revision History:
	*		alexanderl - 01/11/2008 - commented rule 6b [alexander][mantis:13793]
	*		adam.englander - 05/02/2007 - Deprecated $direct_deposit effect
				$direct_deposit was utilized in an old version of ecash and speficially for CLK
				CLK no longer allows for non-direct deposit and the existing users of this
				calculator do not want the direct_deposit flag to effect the loan amount
	*		alexanderl - 2008-07-22 - modified determination of fund_amount based on the new rules [#8081]

    	@param $monthly_net string Monthly Income
    	@param $direct_deposit string Direct Deposit ** Deprecated - Has no effect on amount **
    	@param int $react_app_id Application ID used to check eCash react rules
    	@return $fund_amount string Fund Amount
    */
	public function Calculate_React_Loan_Amount(
			$monthly_net, 
			$direct_deposit=NULL, 
			$react_app_id = 0, 
			$frequency_name = NULL) //mantis:9786 - added $frequency_name
	{
		//Normalize $frequency_name
		$frequency_name = strtolower($frequency_name);
		
		if($this->use_old_config)
		{
			$fund_amount =  $this->Calculate_Loan_Amount($monthly_net, $direct_deposit);
		}
		else
		{
			// New Business Rules for React
			// Get Original New Loan Amount
			$new_loan_amount = $this->Calculate_Loan_Amount($monthly_net);
			$fund_amount = $new_loan_amount;

			// Calculate Max Fund Ammount.
			// (Trick Calculate Loan instead of rewriting the function)
			$orig_amounts = $this->config->new_loan_amount;
			$react_amounts = $this->config->max_react_loan_amount;
			$react_amounts_ecash = $this->config->max_react_loan_amount_ecash; //mantis:9786
			
			/**
			 * The reason this defaults to 100 is that prior to these business 
			 * rules being added this value was essentially hard coded to 100.
			 * So now if the rule doesn't exist, we can safely assume that 100 
			 * should be used and we will be safely backwards compatible.
			 */
			$recovered_deduction = isset($this->config->react_deductions['recovered']) ? $this->config->react_deductions['recovered'] : 100;
			$quickcheck_deduction = isset($this->config->react_deductions['quickcheck']) ? $this->config->react_deductions['quickcheck'] : 100;
			
			$this->config->new_loan_amount = $react_amounts;
			$this->config->new_loan_amount = $orig_amounts;

			$returned_items = 0;
			$in_collections = FALSE;
			$quickcheck_pending = FALSE;
			$paid_by_other = FALSE;
			$zero_qc_balance = FALSE;
			$second_tier_collections = FALSE;

			// If we passed in a valid application_id, we need to check additional rules
			if($react_app_id > 0)
			{
				$returned_items = Returned_Item_Count($react_app_id, $this->ldb);
				$in_collections = Was_In_Collections($react_app_id, $this->ldb);
				$quickcheck_pending = QuickChecks_Pending($react_app_id, $this->ldb);
				$balance = Get_Balance($react_app_id, $this->ldb);
				$completed_qc = Has_Completed_Quickchecks($react_app_id, $this->ldb);
				$second_tier_collections = Second_Tier_Collections_Paid($react_app_id, $this->ldb);
				$fund_amount = Previous_Loan_Amount($react_app_id, $this->ldb);
				$app_status = Previous_Loan_Status($react_app_id, $this->ldb);
				$most_recent_fund_amount = Most_Recent_Loan_Amount($react_app_id, $this->ldb); //#8081
				if (empty($most_recent_fund_amount))
				{
					throw new Exception(sprintf(
						'received no most recent fund amount for app %s',
						$react_app_id)
					);
				}
			}

			// Please Review "ExamplesforReactSpec3-19" for reference documentation.

			// Inactive (Paid)
			// Mantis #11748 - check for wap react so we don't increase the loan amount.  [RV]
			if($app_status == 'paid' && !$this->is_wap)
			{
				// Rule 6.1 #1 c
				if ($returned_items > 1 && !$completed_qc)
				{
					$fund_amount = $most_recent_fund_amount;
				}
				// Rule 6.1 #1 a (2?)
				else if ($balance == 0 && $completed_qc)
				{
					$fund_amount = $most_recent_fund_amount - $quickcheck_deduction;
				}
				// 6.1 #1 b (else if($returned_items == 1 && !$in_collections))
				// 6.1 #1 a (default react loan amount)
				else
				{
					$fund_amount = max($fund_amount + $this->config->react_amount_increase, $new_loan_amount);
				}

			}
			// Inactive (Recovered)
			else if($app_status == 'recovered')
			{
				// 6.1 #2 b
				$fund_amount = $most_recent_fund_amount - $recovered_deduction;
			}
			// 2nd Teir Allowed
			else if($app_status == 'allowed')
			{
				$fund_amount = $most_recent_fund_amount;
			}			

			// Make Sure Loan is at least 150
			$fund_amount = ($fund_amount < 150) ? 150 : $fund_amount;

			// GForge #9013 & Mantis #9786 - Only run frequency business rules for eCash
			if (isset($react_amounts_ecash[$frequency_name]) && $this->is_ecash_react)
			{

				// this will ensure that the amounts based only on frequency will come first.
				ksort($react_amounts_ecash);

				// $freq_max is used so that there can be a default amount based on
				// the frequency, but that amount can be overridden with a higher amount
				// if the application matches the frequency and a pay range.
				$freq_max = $fund_amount;
				$frequency_only = FALSE;
				foreach ($react_amounts_ecash as $key => $amount)
				{
					// business rules with amounts should be $frequency-$minimum_amount-$max_amount
					list($frequency, $low, $high) = explode('-', $key);
					if ($frequency_name == $frequency)
					{
						if (is_null($low) && is_null($high))
						{
							$freq_max = min($freq_max, $amount);
							$frequency_only = TRUE;
						}
						elseif ($monthly_net >= (int)$low
							&& (is_null($high)
								|| $monthly_net <= (int)$high
							))
						{
							if ($frequency_only)
							{
								$freq_max = $fund_amount;
							}
							$freq_max = min($freq_max, $amount);
							$frequency_only = FALSE;
						}
					}
				}

				$fund_amount = min($fund_amount, $freq_max);

			}
			else
			{
				$react_amounts = array_reverse($react_amounts);
				foreach($react_amounts as $income_react => $react_amount)
				{
					if($monthly_net > $income_react)
					{
						$fund_amount = ($fund_amount > $react_amount) ? $react_amount : $fund_amount;
						break;
					}
				}
				//$fund_amount = 0;
			}
		}
		return $fund_amount;
	}

    /**
		@publicsection
		@public
		@brief
			Calculate Loan Amount

		Revision History:
			alexanderl - 09/13/2007 - changed logic for ecash3.0 [mantis:8330]
			alexanderl - 09/14/2007 - returned to the previous state [mantis:8330]
			adam.englander - 05/02/2007 - Deprecated $direct_deposit effect
				$direct_deposit was utilized in an old version of ecash and speficially for CLK
				CLK no longer allows for non-direct deposit and the existing users of this
				calculator do not want the direct_deposit flag to effect the loan amount

    	@param $monthly_net string Monthly Income
    	@param $direct_deposit string Direct Deposit ** Deprecated - Has no effect on amount **
    	@return $fund_amount string Fund Amount
    */
	public function Calculate_Loan_Amount($monthly_net, $direct_deposit=NULL, $campaign_short=NULL)
	{
		$fund_amount = NULL;

		$amounts = $this->config->new_loan_amount;

		// sort fund amounts by minimum income,
		// largest first
		krsort($amounts);
		
		// find the greatest fund amount for
		// our monthly income
		foreach ($amounts as $income => $amount)
		{
			if ($monthly_net < $income)
			{
				$fund_amount = $amount;
			}
			else
			{
				break;
			}
		}

		if (!$fund_amount)
		{
			$fund_amount = reset($amounts);
		}

		if (!is_null($campaign_short)
			&& isset($this->config->new_loan_campaign_bonuses[$campaign_short]))
		{
			$fund_amount += $this->config->new_loan_campaign_bonuses[$campaign_short];
		}

		if (!is_numeric($fund_amount)) $fund_amount = FALSE;
		return($fund_amount);

	}

    /**
		@publicsection
		@public
		@brief
			Estimate Fund Datet


    	@return $due_date string Due Date
    */
	public function Estimate_Fund_Date ($app_id = NULL, $preact = FALSE)
	{
		if($preact)
		{
			$info = Pending_Payment_Info($app_id, $this->ldb);
			$fund_date = strtotime($info->date_effective) + ($info->pending_period * 86400);
		}
		else
		{
			// due_date = Today + 1 day
			$fund_date = mktime(4,0,0) + 86400;
		}

		// _Is_Weekend?
		// _Is_Holiday?
		$fund_date = $this->_Get_Next_Valid_Day($fund_date);

		// return Day
		return($fund_date);

	}


    /**
		@publicsection
		@public
		@brief
			Create Paydates (deprecated: Use the Paydate_Calc)

    	@param $income_frequency string Income Freq.
    	@param $pay_date string Pay date 1
    	@param $pay_date2 string Pay date 2
    	@return $pay_dates array Pay Dates
    */
	public function Create_Paydates($income_frequency, $pay_date1, $pay_date2)
	{
		//Set hour to 4 am just to get around any daylight savings issues
		$p1 = mktime(4,0,0,substr($pay_date1,5,2),substr($pay_date1,8,2),substr($pay_date1,0,4));
		$p2 = mktime(4,0,0,substr($pay_date2,5,2),substr($pay_date2,8,2),substr($pay_date2,0,4));

		switch ( $income_frequency )
		{
			case "WEEKLY":
				$pay_date3 = $p2+604800; //+1 Week
				$pay_date4 = $p2+1209600; //+2 Weeks
			break;
			case "BI_WEEKLY":
				$pay_date3 = $p2+1209600; //+2 Weeks
				$pay_date4 = $p2+2419200; //+4 Weeks
			break;
			case "FOUR_WEEKLY":
				$pay_date3 = $p2+(1209600*2); //+4 Weeks
				$pay_date4 = $p2+(2419200*2); //+8 Weeks
			break;
			case "TWICE_MONTHLY":
				$pay_date3 = mktime(4,0,0,date("m",$p1)+1,date("d",$p1),date("Y",$p1)); //+1 Month
				$pay_date4 = mktime(4,0,0,date("m",$p2)+1,date("d",$p2),date("Y",$p2));; //+1 Month
			break;
			case "MONTHLY":
				$pay_date3 = mktime(4,0,0,date("m",$p2)+1,date("d",$p2),date("Y",$p2)); //+1 Month
				$pay_date4 = mktime(4,0,0,date("m",$p2)+2,date("d",$p2),date("Y",$p2)); //+2 Months
			break;
		}

		while( $this->_Is_Weekend($pay_date3) || $this->_Is_Holiday($pay_date3) )
		{
			$pay_date3 += 86400;
		}

		while( $this->_Is_Weekend($pay_date4) || $this->_Is_Holiday($pay_date4) )
		{
			$pay_date4 += 86400;
		}

		return array("pay_date3" => date("Y-m-d",$pay_date3), "pay_date4" => date("Y-m-d",$pay_date4));
	}

    /**
		@publicsection
		@public
		@brief
			_Get_Next_Valid_Day

    	@param $day string Day
    	@param $direct_deposit Defaulted to FALSE so it doesn't mess up any other calls
    		to this function.
    	@return $day string Next Valid Day
    */
	public function _Get_Next_Valid_Day ($day, $direct_deposit = FALSE)
	{
		while ($this->_Is_Weekend ($day) || $this->_Is_Holiday ($day))
		{
			// This mirrors the way pay_day_calc works. The next step will be to have pay_day_calc
			// be used in this class instead of doing its own calculations.
			// Note: I had to force it to $direct_deposit = FALSE so that it didn't mess up any
			// other calls to _Get_Next_Valid_Day(). [BF] - Mantis #7950
			if($direct_deposit)
			{
				// Subtract 1 day
				$day -= 86400;
			}
			else
			{
				// Add 1 day
				$day += 86400;
			}
		}

		return $day;
	}

    /**
		@publicsection
		@public
		@brief
			_Is_Holiday

    	@param $date string Date
    	@return $holiday bool Is a Holiday
    */
	public function _Is_Holiday($date)
	{
		if(in_array(date("Y-m-d", $date), $this->holiday_array, TRUE))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

    /**
		@publicsection
		@public
		@brief
			_Is_Weekend

    	@param $date string Date
    	@return $weekend bool Is a Weekend Day
    */
	public function _Is_Weekend($date)
	{
		if (date ("w", $date) === "0" || date ("w", $date) === "6")
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Check the validity of the due date
	 *
	 * @param int $fund_date
	 * @param int $due_date
	 * @return bool
	 */
	public function checkDueDate($fund_date, $due_date)
	{
			$grace_period = $this->getGracePeriod();

			// Normalize the fund_date to remove any time.
			$due_date_check = mktime(4,0,0,date("m",$fund_date),date("d",$fund_date),date("Y",$fund_date));
			
			// Add in our grace period.
			$due_date_check += $grace_period;
			
			// Renormalize because DST may make this value 3AM or 5AM, which would be bad.
			$due_date_check = mktime(4,0,0,date("m",$due_date_check),date("d",$due_date_check),date("Y",$due_date_check));
			
			return ($due_date > $due_date_check);
			
	}

	/**
	 * Return the grace period duration in seconds based on the business rule
	 *
	 * @return int
	 */
	public function getGracePeriod()
	{
		// If grace_period hasn't been caculated and set, set it
		if (empty($this->grace_period))
		{
			if (is_array($this->config->grace_period) && isset($this->config->grace_period['grace_period']))
			{
				$this->grace_period = $this->config->grace_period['grace_period'] * 86400;
			}
			elseif (isset($this->config->grace_period))
			{
				$this->grace_period = $this->config->grace_period * 86400;
			}
			else
			{
				//Default, 10 days
				$this->grace_period = 864000;
			}
		}
		return $this->grace_period;
	}
	
    /**
		@publicsection
		@public
		@brief
			 Finance_Info

    	@param $payoff_date timestamp Pay Off Date
    	@param $fund_date timestamp Fund Date
    	@param $loan_amount string Loan Amount
    	@return $weekend bool Is a Weekend Day
    */
		public function Loan_Due_Date($fund_date, $pay_date_array, $direct_deposit)
		{
			foreach ($pay_date_array as $pay_date)
			{
				$d = getdate(strtotime($pay_date));

				if ((!$direct_deposit) || ($direct_deposit === 'FALSE'))
				{
					$d['mday']++;
				}

				//Make 4am for daylight savings problems
				$due_date = mktime(4, 0, 0, $d['mon'], $d['mday'], $d['year']);

				// correct for weekends, etc.
				$due_date = $this->_Get_Next_Valid_Day($due_date, $direct_deposit);

				if ($this->checkDueDate($fund_date, $due_date))
				{
					return $due_date;
				}
			}

			return FALSE;
		}

    /**
		@publicsection
		@public
		@brief
			 Finance_Info

    	@param $payoff_date timestamp Pay Off Date
    	@param $fund_date timestamp Fund Date
    	@param $loan_amount string Loan Amount
    	@return $finance array Financial Infor
    */
	public function Finance_Info($payoff_date, $fund_date, $loan_amount, $finance_charge = NULL)
	{

		$days = round(($payoff_date - $fund_date) / 60 / 60 / 24 );

		if ($days < 1)
		{
			$days = 1;
		}

		if (!isset($finance_charge))
		{
			// order: grouped business rule value, non-grouped value, default
			if (isset($this->config->service_charge['svc_charge_percentage']))
			{
				$chg_pct = $this->config->service_charge['svc_charge_percentage'];
			}
			elseif (isset($this->config->svc_sharge_percentage))
			{
				$chg_pct = $this->config->svc_sharge_percentage;
			}
			else
			{
				$chg_pct = 30;
			}

			$finance_charge = round($loan_amount * $chg_pct / 100);
		}

		if ($loan_amount != 0)
		{
			$apr = round( (($finance_charge / $loan_amount / $days) * 365 * 100), 2);
		}
		else
		{
			$apr = 0;
		}
		$total_payments = ($loan_amount + $finance_charge);

		if (is_numeric($finance_charge) && is_numeric($apr) && is_numeric($total_payments))
		{

			$return = array();
			$return['finance_charge'] = $finance_charge;
			$return['apr'] = $apr;
			$return['total_payments'] = $total_payments;

		}
		else
		{
			$return = FALSE;
		}

		return($return);

	}


    /**
		@publicsection
		@public
		@brief
			Qualify_Person

    	@param $pay_dates array dates as a string (not sure about format)
    	@param $pay_span string MONTHLY, WEEKLY, TWICE_MONTHLY, BI_WEEKLY, FOUR_WEEKLY
    	@param $pay int Monthly income
    	@param $direct_deposit bool Boolean Direct Deposit
    	@param $job_length date Job Length (YYYY-MM-DD)
    	@return $loan_info array Loan Information
    */
	public function Qualify_Person($pay_dates, $pay_span, $pay, $direct_deposit, $job_length, $loan_amount = NULL, $react_loan = FALSE, $react_app_id = NULL, $preact = FALSE)
	{
		// use real booleans, and make things easier on ourselves later!
		// this should be handled during normalization -- NOT HERE
		$direct_deposit = (($direct_deposit=='TRUE') || ($direct_deposit==1) || ($direct_deposit === TRUE)) ? TRUE: FALSE;

		// this is a misnomer -- we're actually calculating
		// our net per PAYCHECK, not per month
		$monthly_net = $this->Calculate_Monthly_Net($pay_span, $pay);

		// these checks are now done in validation,
		// so there's not really any reason for them here
		$valid = TRUE;
		//if ($valid) $valid = $this->Check_Income($income_source, $monthly_net);
		//if ($valid) $valid = $this->Check_Job_Length($job_length);

		if (!is_numeric($loan_amount))
		{
			// find their loan amount if this happens to turn into a React loan becasue they new customer
			// matches an existing application details treat it as a React Loan
			if($react_loan)
			{
				$loan_amount = $this->Calculate_React_Loan_Amount($monthly_net, $direct_deposit);
			}
			else
			{
				$loan_amount = $this->Calculate_Loan_Amount($monthly_net, $direct_deposit);
			}
		}

		if (is_numeric($monthly_net) && is_numeric($loan_amount) && $valid)
		{

			// get our estimated fund date
			$fund_date = $this->Estimate_Fund_Date($react_app_id, $preact);

			// get our due date
			$payoff_date = $this->Loan_Due_Date($fund_date, $pay_dates, $direct_deposit);

			// calculate our financing information
			$finance_info = $this->Finance_Info($payoff_date, $fund_date, $loan_amount);

			$loan_info = array();
			$loan_info['fund_date'] = date('Y-m-d', $fund_date);
			$loan_info['payoff_date'] = date('Y-m-d', $payoff_date);
			$loan_info['fund_amount'] = $loan_amount;
			$loan_info['net_pay'] = $monthly_net;
			$loan_info = array_merge($loan_info, $finance_info);

		}
		else
		{

			// kept for compatibility only
			$loan_info = array('errors' => $this->errors);

		}

		return($loan_info);

	}

	/**
		@privatecsection
		@private
		@brief
			Get Configurtaion (Management Database)

    	@param $property_short string property_short
    	@return $prop object Config Object
    */
	private function Get_Config($property_short)
	{

			$config = NULL;

	    try {

	  		$query = "SELECT qualify FROM property_map WHERE property_short='{$property_short}' LIMIT 1";
	      $result = $this->sql->Query('management', $query);

	      if ($result)
	      {

	      	if ($row = $this->sql->Fetch_Array_Row($result))
	      	{

	      		// unserialize object
	      		$config = @unserialize($row['qualify']);

	      		if (is_object($config))
	      		{

	      			// store this
	      			$this->config = $config;
	      			$this->config->new_loan_amount = $this->config->amounts;

	      		}
	      		else
	      		{
	      			$this->app_log->Write("Qualify: Unable to unserialize qualify object : {$row['qualify']}");
	      		}

	      	}
	      	else
	      	{
	          $this->app_log->Write("Qualify - no rows were returned: $query");
	      	}

	      }
	      else
	      {
	      	$this->app_log->Write("Qualify query failed: $query");
	      }

	    }
	    catch (MySQL_Exception $e)
	    {
	    	// just let 'em go
	    }

	    if (!is_object($config)) $config = FALSE;
	    return($config);

	}

	/**
	 * Sets the loan type name.
	 *
	 * @param string $name
	 * @return void
	 */
	public function setLoanTypeName($name)
	{
		$this->loan_type_name = $name;
	}

	/**
	 * Returns the loan type name.
	 *
	 * @return string
	 */
	protected function Get_Rule_Config_Loan_Type()
	{
		return $this->loan_type_name;
	}

       /**
               @privatecsection
		@private
		@brief
			Get Configurtaion (New Business Rules)

    	@param $property_short string property_short
    	@return $prop object Config Object
    */
	private function Get_Rule_Config($property)
	{
		$config = NULL;
		$biz_rules = new Business_Rules($this->ldb);

		if (!$this->loan_type_id)
		{
    	$this->loan_type_id = $biz_rules->Get_Loan_Type_For_Company($property, $this->Get_Rule_Config_Loan_Type());
		}

		$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($this->loan_type_id);
		$rule_set = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

		$_SESSION['config']->loan_type_id = $this->loan_type_id;
		$_SESSION['config']->rule_set_id = $rule_set_id;

		if(count($rule_set))
		{
			$config =  new stdClass();
			foreach($rule_set as $rule => $value)
			{
				$config->$rule = $value;
			}

			if($this->Validate_Rule_Config($config))
			{
				$this->config = $config;
			}
			else
			{
				$this->app_log->Write("Qualify: One or more Business Rules are not Set.");
			}
		}
		else
		{
			$config = FALSE;
			$this->config = $config;
			$this->app_log->Write("Qualify: Unable to obtain Business Rules.");
		}

	    if (!is_object($config)) $config = FALSE;
	    return($config);

	}

	protected function Validate_Rule_Config($config)
	{
		return ($config->react_amount_increase
			&& $config->new_loan_amount
			&& $config->max_react_loan_amount);
	}
 
 
	/**
	 * Returns the array of valid loan amounts
	 *
	 * @return array
	 */
	public function Get_Rule_Config_Loan_Amounts()
	{
		return $this->config->new_loan_amount;
	}
}

?>
