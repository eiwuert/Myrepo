<?php
/**
 * Interface for qualification
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
interface VendorAPI_IQualify
{
	/**
	 * Runs qualification for the customer
	 *
	 * @return void
	 */
	public function qualifyApplication(array $data);

	/**
	 * Recalculates finance information with the given parameters
	 *
	 * @param int $amount
	 * @param int $due_date
	 * @return void
	 */
	public function calculateFinanceInfo($amount, $fund_date, $due_date);

	/**
	 * Calculates the maximum loan amount the customer qualifies for
	 * @return int
	 */
	public function getMaximumLoanAmount();

	/**
	 * Calculates the amounts available
	 *
	 * @param Integer $fund_amount
	 * @param boolean $is_react
	 * @return array
	 */
	public function getAmountIncrements($fund_amount, $is_react);

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
	public function getLoanAmount();

	/**
	 * Calculates the APR
	 *
	 * @return float
	 */
	public function getAPR();

	/**
	 * Calculates the fund date estimate
	 *
	 * @return DateTime
	 */
	public function getFundDateEstimate();

	/**
	 * Calculates the first payment date
	 *
	 * @return DateTime
	 */
	public function getFirstPaymentDate();

	/**
	 * Returns the finance charge.
	 *
	 * @return int
	 */
	public function getFinanceCharge();

	/**
	 * Returns the total payment amount.
	 *
	 * @return int
	 */
	public function getTotalPayment();

	/**
	 * Returns a QualifyInfo object base on this qualify data
	 *
	 * @return QualifyInfo
	 */
	public function getQualifyInfo();

	/**
	 *
	 * @param $data
	 * @return QualifyInfo
	 */
	public function getPaydates($data);
}
