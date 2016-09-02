<?php
/**
 * Base abstract class for the Qualify class.
 *
 * This just implements the basic getters once the application has been qualified.
 *
 * @author Brian Feaver <brian.feaver>
 */
abstract class VendorAPI_Qualify implements VendorAPI_IQualify
{
	const OLP_PROCESS_ECASH_REACT = 'ecashapp_react';

	/**
	 * @var string
	 */
	protected $fund_date;

	/**
	 * @var string
	 */
	protected $payoff_date;

	/**
	 * @var int
	 */
	protected $fund_amount;

	/**
	 * @var int
	 */
	protected $net_pay;

	/**
	 * @var int
	 */
	protected $finance_charge;

	/**
	 * @var float
	 */
	protected $apr;

	/**
	 * @var int
	 */
	protected $total_payments;

	/**
	 * @var array
	 */
	protected $paydates = array();

	/**
	 * Calculates the maximum loan amount the customer qualifies for
	 *
	 * @return int
	 */
	public function getMaximumLoanAmount()
	{
		if (!isset($this->max_fund_amount)) throw new RuntimeException('need to qualify the application first');
		return $this->max_fund_amount;
	}

	public function getLoanAmount()
	{
		if (!isset($this->fund_amount)) throw new RuntimeException('need to qualify the application first');
		return $this->fund_amount;
	}

	/**
	 * Calculates the APR
	 *
	 * @return float
	 */
	public function getAPR()
	{
		if (!isset($this->apr)) throw new RuntimeException('need to qualify the application first');
		return $this->apr;
	}

	/**
	 * Calculates the fund date estimate
	 *
	 * @return DateTime
	 */
	public function getFundDateEstimate()
	{
		if (!isset($this->fund_date)) throw new RuntimeException('need to qualify the application first');
		return $this->fund_date;
	}

	/**
	 * Calculates the first payment date
	 *
	 * @return DateTime
	 */
	public function getFirstPaymentDate()
	{
		if (!isset($this->payoff_date)) throw new RuntimeException('need to qualify the application first');
		return $this->payoff_date;
	}

	/**
	 * Determines if we are an ecash react.
	 *
	 * @param array $data
	 * @return bool
	 */
	protected function isEcashReact(array $data)
	{
		return !strcasecmp($data['olp_process'], self::OLP_PROCESS_ECASH_REACT);
	}

	/**
	 * Determines if we are a react
	 *
	 * @param array $data
	 * @return bool
	 */
	protected function isReact(array $data)
	{
		$val = $data['is_react'];
		$react_flag = (is_bool($val) ? $val : strcasecmp($val, 'yes') == 0);
		
		return $react_flag || $this->isEcashReact($data);
	}

	/**
	 * Defined in VendorAPI_IQualify
	 *
	 * @return int
	 */
	public function getFinanceCharge()
	{
		if (!isset($this->finance_charge)) throw new RuntimeException('need to qualify the application first');
		return $this->finance_charge;
	}

	/**
	 * Defined by VendorAPI_IQualify
	 *
	 * @return int
	 */
	public function getTotalPayment()
	{
		if (!isset($this->total_payments)) throw new RuntimeException('need to qualify the application first');
		return $this->total_payments;
	}
	/**
	 *  Returns a QualifyInfo object base on this qualify data
	 *
	 *  @return QualifyInfo
	 */
	public function getQualifyInfo()
	{
		return new VendorAPI_QualifyInfo(
			$this->getMaximumLoanAmount(),
			$this->getLoanAmount(),
			$this->getAPR(),
			$this->getFundDateEstimate(),
			$this->getFirstPaymentDate(),
			$this->getFinanceCharge(),
			$this->getTotalPayment(),
			$this->paydates
		);
	}
}
