<?php

/**
 * Validates an applications paydate model.
 *
 * @author Russell Lee <russell.lee@sellingsource.com>
 *
 */
class VendorAPI_CFE_Conditions_ValidPaydateModel implements ECash_CFE_ICondition, ECash_CFE_IExpression
{
	// CFE, CFE, what will we do with you.
	public function __construct() { }

	/**
	 * Evaluates an applications
	 *
	 * @param ECash_CFE_IContext $c
	 */
	public function evaluate(ECash_CFE_IContext $c)
	{
		return $this->isValid($c);
	}

	/**
	 * Evaluates an applications
	 *
	 * @param ECash_CFE_IContext $c
	 */
	public function isValid(ECash_CFE_IContext $c)
	{
		$income_frequency = $c->getAttribute('income_frequency');
		$paydate_model = $c->getAttribute('paydate_model');
		$day_of_week = $c->getAttribute('day_of_week');

		if (
			($income_frequency == 'weekly' && $paydate_model == 'dw')
			|| ($income_frequency == 'twice_monthly' && $paydate_model == 'wwdw')
			|| ($income_frequency == 'bi_weekly' && $paydate_model == 'dwpd')
			|| ($income_frequency == 'monthly' && $paydate_model == 'wdw')
			|| ($income_frequency == 'monthly' && $paydate_model == 'dwdm')
		)
		{
			return ($day_of_week != 'sat' && $day_of_week != 'sun');
		}

		return TRUE;
	}
}

?>
