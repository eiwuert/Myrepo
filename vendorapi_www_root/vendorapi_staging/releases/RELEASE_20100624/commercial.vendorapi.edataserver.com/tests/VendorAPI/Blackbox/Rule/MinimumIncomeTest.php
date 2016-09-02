<?php
/**
 * Tests the MinimumIncome failure reason.
 *
 * @package OLPBlackbox
 * @subpackage FailureReasons
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_MinimumIncomeTest extends PHPUnit_Framework_TestCase
{
	protected $event_log = "";
	
	public function setUp()
	{
		$this->event_log = $this->getMock("VendorAPI_Blackbox_EventLog", array(), array(), '', FALSE);
	}
	
	/**
	 * Data provider for the main test for this unit test.
	 *
	 * @return array Multi-dimentional array.
	 */
	public static function mainDataProvider()
	{
		return array(
			array(50.00, FALSE, FALSE),
			array(150.00, TRUE, TRUE),
		);
	}
	/**
	 * Tests {@see VendorAPI_Blackbox_Rule_MinimumIncome} failing with list present.
	 *
	 * Make sure that when MinimumIncome fails it sets a reason for doing so.
	 * The {@see OLPBlackbox_FailureReason_FactoryTest} class will ensure that
	 * when the Blackbox mode is ECASH_REACT that the failure list will be 
	 * present.
	 * 
	 * @dataProvider mainDataProvider
	 * 
	 * @param int $income The income to set in Blackbox_Data.
	 * @param bool $expected_result The outcome from isValid()
	 * @param bool $failure_reasons Whether the failure list is empty after the run.
	 * 
	 * @return void
	 */
	public function testRun($income, $expected_result, $failure_reasons)
	{
		$bb_state = new VendorAPI_Blackbox_StateData();
		$data = new Blackbox_Data();
		
		$rule = $this->getMock('VendorAPI_Blackbox_Rule_MinimumIncome', array('getDataValue', 'getRuleValue'), array($this->event_log));
		$rule->expects($this->any())->method('getRuleValue')->will($this->returnValue(100.00));
		$rule->expects($this->any())->method('getDataValue')->will($this->returnValue($income));
		
		$this->assertEquals($expected_result, $rule->isValid($data, $bb_state));
		$this->assertEquals($failure_reasons, empty($bb_state->failure_reason));
	}
}

?>
