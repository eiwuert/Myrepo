<?php

/**
 * Validates an applications paydates.
 *
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class VendorAPI_CFE_Conditions_ValidPaydates implements ECash_CFE_ICondition, ECash_CFE_IExpression
{

	protected $paydates;

	public function __construct($paydates)
	{
		$this->paydates = $paydates;
	}

	public function evaluate(ECash_CFE_IContext $c)
	{
		return $this->isValid($c);
	}

	/**
	 * Evaluates an applications
	 */
	public function isValid(ECash_CFE_IContext $c)
	{
		require_once('pay_date_validation.php');
		$paydates = ($this->paydates instanceof ECash_CFE_IExpression)
			? $this->paydates->evaluate($c)
			: $this->paydates;

		$holidays = new Date_BankHolidays_1(NULL, Date_BankHolidays_1::FORMAT_ISO);
		$holidays = $holidays->getHolidayArray();
		$income_frequency = $c->getAttribute('income_frequency');
		$data = array(
			'pay_date1' => $paydates[0],
			'pay_date2' => $paydates[1],
			'pay_date3' => $paydates[2],
			'pay_date4' => $paydates[3],
			'income_frequency' => $income_frequency
		);
				$validator = new Pay_Date_Validation($data, $holidays);
		$valid = $validator->Validate_Paydates();
		return (count($valid['errors']) < 1 && count($valid['warnings']) < 1);
	}
}