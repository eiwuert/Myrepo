<?php
/**
 * Delegate of paycheck amount.
 *
 * @package VendorAPI
 * @author Demin Yin <Demin.Yin@SellingSource.com>
 */
class LenderAPI_BlackboxDataSource_PaycheckAmount extends LenderAPI_BlackboxDataSource_BaseDelegate
{
	/**
	 * Get paycheck amount.
	 *
	 * @see LenderAPI_BlackboxDataSource_IDelegate::value()
	 * @return int
	 */
	public function value()
	{
		switch ($this->data->income_frequency)
		{
			case 'MONTHLY':
			case 'FOUR_WEEKLY':
				$paycheck_amount = $this->data->income_monthly_net;
				break;
			case 'TWICE_MONTHLY':
			case 'BI_WEEKLY':
				$paycheck_amount = $this->data->income_monthly_net / 2;
				break;
			case 'WEEKLY':
				$paycheck_amount = $this->data->income_monthly_net / 4;
				break;
			default:
				$paycheck_amount = 0;
				break;
		}

		return intval($paycheck_amount);
	}

	/**
	 * Paycheck amount is calculated based on monthly income, thus here we shouldn't 
	 * set paycheck amount directly.
	 * 
	 * @see LenderAPI_BlackboxDataSource_IDelegate::setValue()
	 * @param string $value
	 * @return void
	 */
	public function setValue($value)
	{
	}
}
