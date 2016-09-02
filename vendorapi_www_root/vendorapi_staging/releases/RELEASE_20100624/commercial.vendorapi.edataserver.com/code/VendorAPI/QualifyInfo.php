<?php

/**
 * Loan financing information, such as APR, due date, etc.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_QualifyInfo
{
	/**
	 * @var int
	 */
	protected $max_loan_amount;

	/**
	 * @var int
	 */
	protected $loan_amount;

	/**
	 * @var float
	 */
	protected $apr;

	/**
	 * @var int
	 */
	protected $fund_date;

	/**
	 * @var int
	 */
	protected $first_payment_date;

	/**
	 * @var int
	 */
	protected $finance_charge;

	/**
	 * @var int
	 */
	protected $total_payment;
	
	/**
	 * @var array
	 */
	protected $paydates;

	public function __construct($max_loan_amount, $loan_amount, $apr, $fund_date,
		$first_payment_date, $finance_charge, $total_payment, array $paydates = null)
	{
		$this->max_loan_amount = $max_loan_amount;
		$this->loan_amount = $loan_amount;
		$this->apr = $apr;
		$this->fund_date = $fund_date;
		$this->first_payment_date = $first_payment_date;
		$this->finance_charge = $finance_charge;
		$this->total_payment = $total_payment;
		$this->paydates = $paydates;
	}

	/**
	 * Calculates the maximum loan amount the customer qualifies for
	 * @return int
	 */
	public function getMaximumLoanAmount()
	{
		return $this->max_loan_amount;
	}

	/**
	 * Gets the loan amount that we qualified the customer for
	 *
	 * Currently, this only varies from the maximum loan amount when the
	 * agent requests a different amount during an ecash react. However,
	 * this is ALWAYS the amount that should be paired with the finance
	 * information (getAPR, etc.).
	 *
	 * @return int
	 */
	public function getLoanAmount()
	{
		return $this->loan_amount;
	}

	/**
	 * Calculates the APR
	 *
	 * @return float
	 */
	public function getAPR()
	{
		return $this->apr;
	}

	/**
	 * Calculates the fund date estimate
	 *
	 * @return DateTime
	 */
	public function getFundDateEstimate()
	{
		return $this->fund_date;
	}

	/**
	 * Calculates the first payment date
	 *
	 * @return DateTime
	 */
	public function getFirstPaymentDate()
	{
		return $this->first_payment_date;
	}

	/**
	 * Returns the finance charge.
	 *
	 * @return int
	 */
	public function getFinanceCharge()
	{
		return $this->finance_charge;
	}

	/**
	 * Returns the total payment amount.
	 *
	 * @return int
	 */
	public function getTotalPayment()
	{
		return $this->total_payment;
	}
	
	/**
	 * Returns the calculated paydates as timestamps.
	 * @return array int[]
	 */
	public function getPaydates()
	{
		return $this->paydates;
	}

	/**
	 * return this data as an array
	 * @return array
	 */
	public function asArray()
	{
		return array(
			'max_loan_amount' => $this->getMaximumLoanAmount(),
			'loan_amount' => $this->getLoanAmount(),
			'apr' => $this->getAPR(),
			'finance_charge' => $this->getFinanceCharge(),
			'total_payment' => $this->getTotalPayment(),
			'fund_date' => $this->getFundDateEstimate(),
			'first_payment' => date('Y-m-d', $this->getFirstPaymentDate()),
		);
	}
}

?>
