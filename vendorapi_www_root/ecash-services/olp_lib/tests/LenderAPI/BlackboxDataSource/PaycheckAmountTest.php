<?php
/**
 * Test the LenderAPI_BlackboxDataSource_PaycheckAmount class to see if we calculate paycheck amount properly
 * based on monthly income and payment frequency.
 * 
 * @author Demin Yin <Demin.Yin@SellingSource.com>
 * @subpackage LenderAPI
 */
class LenderAPI_BlackboxDataSource_PaycheckAmountTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testing method testValue().
	 * @return array
	 */
	public static function dataProvider()
	{
		return array(
			array(1600, 'MONTHLY',       1600),
			array(2800, 'FOUR_WEEKLY',   2800),
			array(280,  'TWICE_MONTHLY', 140),
			array(1400, 'BI_WEEKLY',     700),
			array(500,  'WEEKLY',        125),
			array(505,  'WEEKLY',        126),
			array(503,  'WEEKLY',        125),
			array(1600, 'WHATEVER',      0),
		);		
	}

	/**
	 * Tests LenderAPI_BlackboxDataSource_PaycheckAmount::value()
	 * @dataProvider dataProvider
	 * @param int $income_monthly_net
	 * @param string $income_frequency
	 * @param int $expected_paycheck_amount
	 * @return void
	 */
	public function testValue($income_monthly_net, $income_frequency, $expected_paycheck_amount)
	{
		$obj_blackbox_data = new OLPBlackbox_Data();
		$obj_blackbox_data['income_monthly_net'] = $income_monthly_net;
		$obj_blackbox_data['income_frequency']   = $income_frequency;

		$datasource = new LenderAPI_BlackboxDataSource_PaycheckAmount($obj_blackbox_data);
		$this->assertSame($expected_paycheck_amount, $datasource->value());
	}
}
