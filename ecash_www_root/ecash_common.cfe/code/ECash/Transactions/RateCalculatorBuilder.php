<?php

class ECash_Transactions_RateCalculatorBuilder
{
	protected $rules;
	protected $loan_type_short;
	protected $num_paid;
	protected $override_rate;
	protected $countdown_date;
	
	public function __construct(array $rules, $loan_type_short, $num_paid = NULL, $override_rate = NULL, $countdown_date = NULL)
	{
		$this->rules = $rules;
		$this->loan_type_short = $loan_type_short;
		$this->num_paid = $num_paid;
		$this->override_rate = $override_rate;
		$this->countdown_date = $countdown_date;
	}

	/**
	 * If this function gets out of hand (i.e. too many company specific customizations) it should be
	 * segregated into a company-specific class (Agean_Transactions_RateCalculatorBuilder)
	 */
	public function buildRateCalculator()
	{
		//get percent
		$percentage = $this->getPercent();
		
		//get rounding info
		if (isset($this->rules['interest_rounding'])) {
			$rounder = new ECash_Transactions_Rounder($this->rules['interest_rounding']['type'],$this->rules['interest_rounding']['digit']);
		} else {
			$rounder = new ECash_Transactions_Rounder();
		}
		
		if(strtolower($this->rules['service_charge']['svc_charge_type']) == 'daily')
		{			
			// Sometimes the accrual limit is set in $this->rules['interest_accrual_limit'], we need to account for this possibility
			$accrual_limit = isset($this->rules['service_charge']['interest_accrual_limit']) ? $this->rules['service_charge']['interest_accrual_limit'] : $this->rules['interest_accrual_limit'];
			
			if($this->loan_type_short == 'cso_loan')
			{
				return new ECash_Transactions_CSODailyRateCalculator($rounder, $percentage, $accrual_limit, $this->rules['cso_assess_fee_broker']['percent_amount']);
			}
			else
			{
				return new ECash_Transactions_DailyRateCalculator($rounder, $percentage, $accrual_limit, $this->countdown_date);
			}
		}
		else //if(strtolower($this->rules['service_charge']['svc_charge_type']) == 'fixed') // impact might pass 'standard'
		{			
			return new ECash_Transactions_FixedRateCalculator($rounder, $percentage);
		}
	}

	/**
	 * First check for application rate override.  If not present,
	 * check for 'rate based on loan number.'  If not present return
	 * standard business rule rate.
	 */
	protected function getPercent()
	{
		if(!empty($this->override_rate))
		{
			return $this->override_rate;
		}
		//only check the 'rate based on loan number' if $this->num_paid is set
		elseif($this->num_paid !== NULL && isset($this->rules['loan_rate']))
		{

			// The rate of the loan based on the number of paid accounts
			if(is_array($this->rules['loan_rate']))
			{
				$max = count($this->rules['loan_rate']) - 1;
				if($this->num_paid < $max)
				{
					return $this->rules['loan_rate'][$this->num_paid];
				}
				else
				{
					return $this->rules['loan_rate'][$max];
				}
			}
			else
			{
				return $this->rules['loan_rate'];
			}			
		}
		else
		{
			if(!empty($this->rules['svc_charge_percentage']))
			{
				return $this->rules['svc_charge_percentage'];
			}
			else
			{
				return $this->rules['service_charge']['svc_charge_percentage'];
			}
		}
	}
}

?>
