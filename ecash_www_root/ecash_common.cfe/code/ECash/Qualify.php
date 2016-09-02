<?php
/**
 * Qualify code for ECash commercial.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class ECash_Qualify implements ECash_IQualify
{
	/**
	 * Instance of eCash businness rules object
	 *
	 * @var array
	 */
	protected $business_rules;
	
	/**
	 * Instance of the pay date calculator
	 *
	 * @var Pay_Date_Calc_3
	 */
	protected $pay_date_calc;
	
	/**
	 * Constructor
	 *
	 * @param array $rules
	 * @param Pay_Date_Calc_3 $pay_date_calc
	 */
	public function __construct(array $rules, Pay_Date_Calc_3 $pay_date_calc)
	{
		$this->business_rules = $rules;
		$this->pay_date_calc = $pay_date_calc;
	}
	
	/**
	 * Sets the business rules to $rules.
	 *
	 * @param array $rules
	 * @return void
	 */
	public function setBusinessRules(array $rules)
	{
		$this->business_rules = $rules;
	}
	
	public function setLoanTypeName($loan_type_name) {}

	public function setRateCalculator(ECash_Transactions_IRateCalculator $rate_calculator) {}
	
	/**
	 * Calculates the due date based on the given pay dates and fund date.
	 * 
	 * The paydates MUST already account for direct deposit.
	 *
	 * @param array $pay_dates
	 * @param bool $direct_deposit
	 * @param int $fund_date
	 * @return int
	 */
	public function calculateDueDate(array $pay_dates, $direct_deposit, $fund_date, $is_react = false)
	{
		$direct_deposit = (bool)$direct_deposit;
		$fund_date = (int)$fund_date;
		
		foreach ($pay_dates as $pay_date)
		{
			$pay_date = (int)$pay_date;
			
			$date = getdate($pay_date);
			
			// Normalize the due date
			$due_date = mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']);
			
			$due_date = $this->getNextValidDay($due_date, $direct_deposit);
			
			if ($this->checkGracePeriod($due_date, $fund_date, $is_react))
			{
				return $due_date;
			}
		}
		
		throw new RuntimeException('no valid due date was found with the given paydates');
	}
	
	/**
	 * Returns the next valid business day accounting for direct deposit.
	 *
	 * @param int $date
	 * @param bool $direct_deposit
	 * @return int
	 */
	public function getNextValidDay($date, $direct_deposit)
	{
		while ($this->pay_date_calc->Is_Weekend($date) || $this->pay_date_calc->Is_Holiday($date))
		{
			$date = $direct_deposit ? strtotime('-1 day', $date) : strtotime('+1 day', $date);
		}
		
		return $date;
	}
	
	/**
	 * Checks that $due_date is beyond the grace period.
	 *
	 * @param int $due_date
	 * @param int $fund_date
	 * @return bool
	 */
	public function checkGracePeriod($due_date, $fund_date, $is_react = false)
	{
		$due_date_with_grace = $this->getGracePeriodDate($fund_date, $is_react);
		$d = getdate($due_date_with_grace);
		// renormalize back to 4am in case the grace period changes it
		$due_date_with_grace = mktime(4, 0, 0, $d['mon'], $d['mday'], $d['year']);
		
		return ($due_date > $due_date_with_grace);
	}
	
	/**
	 * Returns the timestamp of the day the graceperiod ends from $date.
	 *
	 * @param int $date
	 * @return int
	 */
	public function getGracePeriodDate($date, $is_react = false)
	{
		$date = (int)$date;
		$today = (int) time();
		
		if ((array_key_exists('grace_period', $this->business_rules)) && ($date > $today))
		{
			$grace = (int)$this->business_rules['grace_period'];

			// Include the reaction due date for the grace period for react apps
			if (($is_react>0) && (array_key_exists('react_grace_date', $this->business_rules))) {
				$react_due_time = strtotime($this->business_rules['react_grace_date']);
				$react_due_offset = $react_due_time - time();
				$react_due_offset = ceil($react_due_offset / (24 * 60 * 60));
				
				if ($react_due_offset > $grace) $grace = $react_due_offset;
			}
		}
		else
		{
			$grace = 10;
		}
		
		return strtotime("+{$grace} days", $date);
	}
}
