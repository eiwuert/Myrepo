<?php
/**
 * Unit test for OLPBlackbox_Enterprise_Generic_Rule_QualifiesForAmount class.
 *
 * @author Adam Englander <adam.Englander@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * Tests the OLPBlackbox_Enterprise_Generic_Rule_QualifiesForAmount class.
 *
 * @author Adam Englander <adam.Englander@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_Rule_QualifiesForAmountTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Mark the test skipped if the classes we need are not declared.
	 *
	 * @return void
	 */
	public function setUp()
	{
		if (!class_exists('OLPECash_CFE_Rules'))
		{
			$this->markTestIncomplete('required classes not found.');
		}
	}
	
	/**
	 * Data provider for {@see testQualifiesForAmount}.
	 * 
	 * @return array multidimentional array of values to test with.
	 */
	public static function qualifiesProvider()
	{
		return array(
			array(0, FALSE, NULL), //0 fund amount should fail qualify rule and not store in state_data
			array(150.00, TRUE, 150.00) // Greater than 0 fund amount shoud pass rull and store in state_data
		);
	}
	
	/**
	 * Tests the basic functionality of OLPBlackbox_Enterprise_Generic_Rule_QualifiesForAmount class.
	 *
	 * @param float $fund_amount the amount that the LoanAmountCalculator would return.
	 * @param bool $result the result we're expecting from isValid()
	 * @param float $stored_value The value we expect to be stored in the state_data
	 * 
	 * @dataProvider qualifiesProvider 
	 * 
	 * @return void
	 */
	public function testQualifiesForAmount($fund_amount, $result, $stored_value)
	{
		$this->config = OLPBlackbox_Config::getInstance();
		$this->config->title_loan = FALSE;
		$this->config->mode = 'LIVE';
		
		$data = new OLPBlackbox_Data();
		$data->income_monthly_net = 6000;


		$init_data = array('campaign_name' => 'CBNK', 'is_react' => FALSE);
		$state_data = new OLPBlackbox_TargetStateData();
		
		
		$qualifies = $this->getMock(
			'OLPBlackbox_Enterprise_Generic_Rule_QualifiesForAmount',
			array('getFundAmount'),
			array($data,$state_data)
		);
		
		$qualifies->expects($this->any())
			->method('getFundAmount')
			->will($this->returnValue($fund_amount));

		$this->assertEquals($result,$qualifies->isValid($data,$state_data));
		$this->assertEquals($stored_value,$state_data->qualified_loan_amount);
	}
}
?>
