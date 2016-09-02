<?php

require_once('OLPBlackboxTestSetup.php');

/**
 * Test the functionality of OLPBlackbox_Enterprise_Generic_Rule_LegacyQualifiesForAmount.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_Rule_LegacyQualifiesForAmountTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Prevent test from running if key classes are not defined.
	 *
	 * @return void
	 */
	protected function setUp()
	{
		if (!class_exists('OLP_Qualify_2'))
		{
			$this->markTestIncomplete(
				sprintf("could not run test %s, not all classes available",
					__CLASS__)
			);
		}
	}
	
	/**
	 * Data provider for {@see testLegacyQualifiesForAmount}.
	 *
	 * @return array Data for test.
	 */
	public static function legacyDataProvider()
	{
		return array(
			// ecash react, which will pass our test
			array(TRUE, FALSE, TRUE, 300.00),
			// regular react, which will pass our test
			array(FALSE, TRUE, TRUE, 300.00),
			// non-react, which will fail
			array(FALSE, FALSE, FALSE, NULL)
		);
	}
	
	/**
	 * Test the functionality of OLPBlackbox_Enterprise_Generic_Rule_LegacyQualifiesForAmount.
	 * 
	 * @param bool $is_react Whether test should behave like the app is a react
	 * @param bool $is_ecash_react test should behave like app is ecash react
	 * @param bool $result the expected result of isValid
	 * @param float $stored_amount the amount expected to be stored in state data
	 * 
	 * @dataProvider legacyDataProvider
	 * 
	 * @return void
	 */
	public function testLegacyQualifiesForAmount($is_react, $is_ecash_react, $result, $stored_amount)
	{
		$config = OLPBlackbox_Config::getInstance();
		if (empty($config->site_config))
		{
			$config->site_config = new stdClass();
		}
		$config->site_config->ecash_react = $is_ecash_react;
		
		$data = new OLPBlackbox_Data();
		$data->income_monthly_net = 6000;
		$data->income_direct_deposit = TRUE;
		$data->income_frequency = 'weekly';

		$init_data = array(
			'target_name' => 'ca', 'name' => 'ca', 'is_react' => $is_react
		);
		$state_data = new OLPBlackbox_TargetStateData($init_data);
		
		// mock up the qualify object that will take the place
		// of legacy OLP_Qualify_2 for this test
		$qualify = $this->getMock('OLP_Qualify_2',
							array('Calculate_React_Loan_Amount', 
								'Calculate_Loan_Amount'),
							array($state_data->target_name));
		$qualify->expects($this->any())
			->method('Calculate_React_Loan_Amount')
			->will($this->returnValue(300.00));
		$qualify->expects($this->any())
			->method('Calculate_Loan_Amount')
			->will($this->returnValue(00.00));
			
		$rule = $this->getMock(
			'OLPBlackbox_Enterprise_Generic_Rule_LegacyQualifiesForAmount',
			array('getQualify', 'setupDB', 'hitEvent', 'hitStat'),
			array($data, $state_data)
		);

		$rule->expects($this->any())
			->method('getQualify')
			->will($this->returnValue($qualify));
			
		$this->assertEquals($result, $rule->isValid($data, $state_data));
		$this->assertEquals($stored_amount, $state_data->qualified_loan_amount);
	}
}

?>
