<?php

require_once('OLPBlackboxTestSetup.php');

/**
 * Test the OLPBlackbox_Enterprise_Agean_Rule_QualifiesForAmount class.
 *
 * @author Adam Englander <adam.Englander@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Agean_Rule_QualifiesForAmountTest extends PHPUnit_Framework_TestCase
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
			array(0, FALSE, NULL, FALSE),
			array(0, TRUE, NULL, FALSE),
			array(150, FALSE, 150, TRUE),
			array(150, TRUE, 150, TRUE),
		);
	}

	/**
	 * Tests the basic functionality of OLPBlackbox_Enterprise_Generic_Rule_QualifiesForAmount class.
	 *
	 * @param float $fund_amount the amount that the LoanAmountCalculator would return.
	 * @param bool $title_loan Is app for title loan?
	 * @param float $stored_value The value we expect to be stored in the state_data
	 * @param bool $result the result we're expecting from isValid()
	 * 
	 * @dataProvider qualifiesProvider 
	 * 
	 * @return void
	 */
	public function testQualifiesForAmount(
			$fund_amount,
			$title_loan,
			$stored_value,
			$result
		)
	{

		$this->config = OLPBlackbox_Config::getInstance();
		$this->config->__unset('title_loan');
		$this->config->__set('title_loan',$title_loan);
		
		// emulate the data from an application
		$data = new OLPBlackbox_Data();
		$data->income_monthly_net = 6450;
		$data->vehicle_make = 'mazda';
		$data->vehicle_vin = '01237101231231';
		$data->vehicle_year = '2005';
		$data->vehicle_model = 'tribute';
		// ?? I dunno what "vehicle_type" must include
		$data->vehicle_type = '2cylfwd';
		// again, whatever.
		$data->vehicle_series = 'le';
		
		// emulate the data for an ITarget who might run this rule
		$campaign_data = array('target_name' => 'cbnk', 
			'name' => 'cbnk', 
			'is_react' => FALSE
		);
		$state_data = new OLPBlackbox_TargetStateData($campaign_data);
		
		$qualifies = $this->getMock(
			'OLPBlackbox_Enterprise_Generic_Rule_QualifiesForAmount',
			array('getFundAmount'),
			array($data,$state_data)
		);
		$qualifies->expects($this->any())
			->method('getFundAmount')
			->will($this->returnValue($fund_amount));
		
		
		$this->assertEquals($result,$qualifies->isValid($data, $state_data));
		$this->assertEquals($stored_value, $state_data->qualified_loan_amount);
	}
	
	/**
	 * Test to make sure that isValid always returns false when performing
	 * a title_loan calculation with no vehicle data
	 *
	 * @return void
	 */
	public function testFailTitleLoanOnNoVehicleData()
	{
		$this->config = OLPBlackbox_Config::getInstance();
		$this->config->__unset('title_loan');
		$this->config->__set('title_loan',TRUE);

		// emulate the data for an ITarget who might run this rule
		$campaign_data = array('target_name' => 'ca', 
			'name' => 'ca', 
			'is_react' => FALSE
		);
		$state_data = new OLPBlackbox_TargetStateData($campaign_data);
		
		$data = new OLPBlackbox_Data();
		$data->income_monthly_net = 6450;
		
		$qualifies = new OLPBlackbox_Enterprise_Agean_Rule_QualifiesForAmount();
		
		$this->assertFalse($qualifies->isValid($data, $state_data));
		$this->assertEquals(NULL, $state_data->qualified_loan_amount);
		
	}
}

?>
