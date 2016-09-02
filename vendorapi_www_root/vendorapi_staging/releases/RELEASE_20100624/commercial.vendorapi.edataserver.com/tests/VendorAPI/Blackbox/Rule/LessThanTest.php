<?php
/**
 * PHPUnit test class for the VendorAPI_Blackbox_Rule_LessThan class.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_LessThanTest extends PHPUnit_Framework_TestCase
{
	protected $event_log = "";
	
	public function setUp()
	{
		$this->event_log = $this->getMock("VendorAPI_Blackbox_EventLog", array(), array(), '', FALSE);
	}
	
	/**
	 * Data provider for testLessThanEquals.
	 *
	 * @return array
	 */
	public static function lessThanDataProvider()
	{
		return array(
			array(30, 20, FALSE), // greater
			array(10, 20, TRUE), // less than
			array(20, 20, FALSE) // equals
		);
	}
	
	/**
	 * Test for a value that's greater than the rule; should return TRUE
	 *
	 * @param bool $data_value the data value used
	 * @param bool $rule_value the rule value used
	 * @param bool $expected   the expected return
	 * @dataProvider lessThanDataProvider
	 * @return void
	 */
	public function testLessThanEquals($data_value, $rule_value, $expected)
	{
		$data = new Blackbox_DataTestObj(array('test' => $data_value));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('VendorAPI_Blackbox_Rule_LessThan', array('hitStat', 'hitEvent'), array($this->event_log));
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'test',
			Blackbox_StandardRule::PARAM_VALUE  => $rule_value,
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertEquals($expected, $v);
	}
}
?>
