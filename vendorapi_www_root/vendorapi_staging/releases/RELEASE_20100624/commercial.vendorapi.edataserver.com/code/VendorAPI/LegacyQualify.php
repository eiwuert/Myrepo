<?php
/**
 * Qualify implementation for HMS/RRV.
 *
 * This is currently a copy of the CLK version of Qualify.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class VendorAPI_LegacyQualify extends VendorAPI_Qualify
{
	/**
	 * The Qualify_2 object
	 *
	 * @var Qualify_2
	 */
	protected $qualify;

	/**
	 * An instance of the Paydate_Handler class
	 *
	 * @var Pay_Date_Calc_3
	 */
	protected $pay_date_calc;

	/**
	 * Constructor
	 *
	 * @param Qualify_2 $qualify
	 * @param Pay_Date_Calc_3 $paydate_calculator
	 */
	public function __construct(Qualify_2 $qualify, Pay_Date_Calc_3 $pay_date_calculator)
	{
		$this->qualify = $qualify;
		$this->pay_date_calc = $pay_date_calculator;
	}

	/**
	 * Runs qualification for the customer
	 *
	 * @param array $data
	 * @return void
	 */
	public function qualifyApplication(array $data, $fund_amount = NULL)
	{
		$pay_dates = $this->getPaydates($data);
		// Thank you Qualify_2!!!
		// You can thank this PITA on Qualify_2's lameness, it'll miscalculate the loan amount if you don't
		// calculate it by itself first and pass it to Qualify_Person
		$this->max_fund_amount = $this->calculateMaximumLoanAmount($data);

		if (!empty($fund_amount))
		{
			$this->fund_amount = $this->getValidatedLoanAmount($fund_amount);
		}
		elseif (($data['olp_process'] == 'ecashapp_react' || $data['olp_process'] == 'cs_react')
			&& !empty($data['loan_amount_desired']))
		{
			$this->fund_amount = $this->getValidatedLoanAmount($data['loan_amount_desired']); 
		}
		else
		{
			$this->fund_amount = $this->max_fund_amount;
		}

		$qualify_info = $this->qualify->Qualify_Person(
			$pay_dates,
			$data['income_frequency'],
			$data['income_monthly'],
			$data['income_direct_deposit'],
			NULL, // $job_length isn't used in Qualify_2
			$this->fund_amount,
			$this->isReact($data),
			isset($data['react_application_id']) ? $data['react_application_id'] : NULL
		);

		// @todo remove this...
		$this->apr = (float)$qualify_info['apr'];
		$this->finance_charge = (int)$qualify_info['finance_charge'];
		$this->total_payments = (int)$qualify_info['total_payments'];
		$this->fund_date = strtotime($qualify_info['fund_date']);
		$this->payoff_date = strtotime($qualify_info['payoff_date']);
		$this->paydates = array_map('strtotime', $pay_dates);

		return new VendorAPI_QualifyInfo(
			$this->max_fund_amount,
			$this->fund_amount,
			$qualify_info['apr'],
			$qualify_info['fund_date'],
			$qualify_info['payoff_date'],
			$qualify_info['finance_charge'],
			$qualify_info['total_payments'],
			$this->paydates
		);
	}

	/**
	 * Calculate payadtes and reutnr them
	 * @param array $data
	 * @return array
	 */
	public function getPaydates($data)
	{
		// Need to calculate all the paydates for the customer
		$paydate_info = new stdClass();
		$paydate_info->paydate_model = $data['paydate_model'];
		$paydate_info->income_frequency = $data['income_frequency'];
		$paydate_info->income_direct_deposit = $data['income_direct_deposit'];
		$paydate_info->last_paydate = $data['last_paydate'];
		$paydate_info->day_string_one = $data['day_of_week'];
		$paydate_info->day_int_one = $data['day_of_month_1'];
		$paydate_info->day_int_two = $data['day_of_month_2'];
		$paydate_info->week_one = $data['week_1'];
		$paydate_info->week_two = $data['week_2'];

		// Calculate the first due date
		// Like below, this is Qualify_2 being stupid. It needs unmodified paydates (not accounting for direct deposit)
		// so we're assuming that direct deposit is TRUE when getting the pay dates.
		return $this->pay_date_calc->Calculate_Pay_Dates($paydate_info->paydate_model, $paydate_info);
	}

	/**
	 * (non-PHPdoc)
	 * @see code/VendorAPI/VendorAPI_IQualify#calculateFinanceInfo()
	 */
	public function calculateFinanceInfo($amount, $fund_date, $due_date)
	{
		$amount = $this->getValidatedLoanAmount($amount);

		if (!$this->qualify->checkDueDate($fund_date, $due_date))
		{
			throw new InvalidArgumentException('Due date is not valid');
		}

		$qualify_info = $this->qualify->Finance_Info(
			$due_date,
			$fund_date,
			$amount
		);
		
		// @todo remove this
		$this->amount = (float)$amount;
		$this->apr = (float)$qualify_info['apr'];
		$this->finance_charge = (int)$qualify_info['finance_charge'];
		$this->total_payments = (int)$qualify_info['total_payments'];
		$this->fund_date = $fund_date;
		$this->payoff_date = $due_date;

		return new VendorAPI_QualifyInfo(
			$amount,
			$amount,
			$qualify_info['apr'],
			$fund_date,
			$due_date,
			$qualify_info['finance_charge'],
			$qualify_info['total_payments'],
			$this->paydates
		);
	}

	/**
	 * Returns the loan amount.
	 *
	 * @param array $data
	 * @return int
	 */
	protected function calculateMaximumLoanAmount(array $data)
	{
		$income_monthly = $data['income_monthly'];
		$direct_deposit = $data['income_direct_deposit'];

		if ($this->isReact($data))
		{
			$react_application_id = $data['react_application_id'];
			$frequency_name = strtolower($data['income_frequency']);

			$this->qualify->setIsEcashReact($this->isEcashReact($data));

			$maximum_amount = $this->qualify->Calculate_React_Loan_Amount(
				$income_monthly,
				$direct_deposit,
				$react_application_id,
				$frequency_name
			);
		}
		else
		{
			$maximum_amount = $this->qualify->Calculate_Loan_Amount(
				$income_monthly,
				$direct_deposit,
				$data['campaign']
			);
		}

		return (int)$maximum_amount;
	}

	/**
	 * Returns validated fund amount 
	 * @param $amount
	 * @return int
	 */
	protected function getValidatedLoanAmount($amount)
	{
		return max(min($amount, $this->max_fund_amount), $this->getMinFundAmount());
	}
	
	/**
	 * Returns the minimum fund amount
	 *
	 * @param bool $is_react
	 * @return int
	 */
	protected function getMinFundAmount($is_react = false)
	{
		return min($this->qualify->Get_Rule_Config_Loan_Amounts());
	}

	protected function getFundIncrement()
	{
		return 50;
	}

	/**
	 * Defined by VendorAPI_IQualify
	 *
	 * @param int $fund_amount
	 * @param bool $is_react
	 * @return array
	 */
	public function getAmountIncrements($fund_amount, $is_react)
	{
		$amount = $this->getMinFundAmount($is_react);
		$end   = $fund_amount;
		$inc   = $this->getFundIncrement();
		$return = array();
		for ($end = $end + ($inc -1);$amount < $end; $amount += $inc)
		{
			$return[] = min($amount, $fund_amount);
		}
		return $return;
	}
}
