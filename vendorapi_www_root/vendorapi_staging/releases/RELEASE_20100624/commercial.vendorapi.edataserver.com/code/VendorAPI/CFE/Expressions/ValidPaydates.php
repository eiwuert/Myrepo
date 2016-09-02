<?php

/**
 * Validates an applications paydates.
 *
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class VendorAPI_CFE_Expressions_ValidPaydates implements ECash_CFE_IExpression
{
	/**
	 * Evaluates an applications
	 */
	public function evaluate(ECash_CFE_IContext $c)
	{
		require_once('pay_date_validation.php');
		$paydates = $c->getAttribute('paydates');
		$holidays = $c->getAttribute('holidays');
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
		return (count($valid['errors']) < 1);
	}
}