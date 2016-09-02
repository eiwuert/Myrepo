<?php

/**
 * The ECash Commercial Loan Amount Calculator adapter
 *
 * This class adapts LoanAmountCalculator to the Vendor API
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class ECash_VendorAPI_LoanAmountCalculator implements VendorAPI_ILoanAmountCalculator
{
	/**
	 * @var LoanAmountCalculator
	 */
	protected $calculator;

	/**
	 * @var array
	 */
	protected $rules;

	/**
	 * Loan type name
	 * @var string
	 */
	protected $loan_type;

	/**
	 * @param LoanAmountCalculator $lac
	 * @param array $business_rules ECash business rules
	 * @param string $loan_type The loan type name short
	 */
	public function __construct(LoanAmountCalculator $lac, array $business_rules, $loan_type)
	{
		$this->calculator = $lac;
		$this->rules = $business_rules;
		$this->loan_type = $loan_type;
	}

	/**
	 * Returns the maximum amount the user qualifies for
	 *
	 * @param array $data
	 * @return float
	 */
	public function getMaximumAmount(array $data)
	{
		$loan = $this->buildData($data);
		return $this->calculator->calculateMaxLoanAmount($loan);
	}

	/**
	 * Calculates the amounts available up to $max
	 * @param float $max
	 * @return array
	 */
	public function getAmountIncrements(array $data)
	{
		$data = $this->buildData($data);
		return $this->calculator->calculateLoanAmountsArray($data);
	}

	/**
	 * Builds the stdClass $data that's passed to the LAC
	 *
	 * @param array $data
	 * @return stdClass
	 */
	protected function buildData(array $data = NULL)
	{
		$is_react = (isset($data['is_react'])
			&& $data['is_react']) ? 'yes' : 'no';
		$income = (float)$data['income_monthly'];
		$num_paid = (int)$data['num_paid_applications'];

		$info = new stdClass();
		$info->business_rules = $this->rules;
		$info->loan_type_name = $this->loan_type;
		$info->income_monthly = $income;
		$info->is_react = $is_react;
		$info->num_paid_applications = $num_paid;
		$info->react_app_id = $data['react_application_id'];
		$info->application_list = $data['application_list'];
		$info->payperiod = $data['income_frequency'];
		$info->idv_increase_eligible = (isset($data['idv_increase_eligible'])
			&& $data['idv_increase_eligible']);
		return $info;
	}
}

?>