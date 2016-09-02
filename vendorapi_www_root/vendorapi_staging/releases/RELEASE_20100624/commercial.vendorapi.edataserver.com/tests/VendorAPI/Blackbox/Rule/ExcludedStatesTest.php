<?php

/**
 * Test that failure reasons are registered when the excluded state rule fails.
 *
 * @package OLPBlackbox
 * @subpackage FailureReasons
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_ExcludedStatesTest extends PHPUnit_Framework_TestCase
{
	protected $event_log = "";
	
	public function setUp()
	{
		$this->event_log = $this->getMock("VendorAPI_Blackbox_EventLog", array(), array(), '', FALSE);
	}
	
	/**
	 * Data provider for {@see testRun}.
	 *
	 * @return array Multi-dimentional array, see params for {@see testRun}.
	 */
	public static function mainDataProvider()
	{
		return array(
			array('VA', FALSE, FALSE),
			array('MI', TRUE, TRUE),
		);
	}
	
	/**
	 * Tests the {@see VendorAPI_Blackbox_Rule_ExcludedStates} rule.
	 *
	 * @dataProvider mainDataProvider
	 * 
	 * @param string $home_state The state reported as home state.
	 * @param boolean $is_valid The expected return value for isValid();
	 * @param boolean $empty_reasons TRUE if failure reasons should be empty
	 * 	after isValid() has been run, FALSE otherwise.
	 */
	public function testRun($home_state, $is_valid, $empty_reasons)
	{
		$state_data = new VendorAPI_Blackbox_StateData();
		$data = new VendorAPI_Blackbox_Data();
		$data->home_state = $home_state;
		
		$rule = $this->getMock('VendorAPI_Blackbox_Rule_ExcludedStates', array('getDataValue', 'getRuleValue'), array($this->event_log));
		$rule->expects($this->any())->method('getRuleValue')->will($this->returnValue(array('WV', 'VA', 'GA')));
		$rule->expects($this->any())->method('getDataValue')->will($this->returnValue($home_state));
		
		$this->assertEquals($is_valid, $rule->isValid($data, $state_data));
		$this->assertEquals($empty_reasons, empty($state_data->failure_reason));
	}
}

?>
