<?php

/**
 * PHPUnit test class for the VendorAPI_Blackbox_Rule_GreaterThanEquals class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_GreaterThanEqualsTest extends PHPUnit_Framework_TestCase
{
	protected $event_log = "";
	
	public function setUp()
	{
		$this->event_log = $this->getMock("VendorAPI_Blackbox_EventLog", array(), array(), '', FALSE);
	}
	
	/**
	 * Test for a value that's greater than the rule; should return TRUE
	 *
	 * @return void
	 */
	public function testGreater()
	{
		$data = new Blackbox_DataTestObj(array('test' => 20));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('VendorAPI_Blackbox_Rule_GreaterThanEquals', array('hitStat', 'hitEvent'), array($this->event_log));
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'test',
			Blackbox_StandardRule::PARAM_VALUE  => 10,
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertTrue($v);
	}

	/**
	 * Test for a value that's equal to the rule; should return TRUE
	 *
	 * @return void
	 */
	public function testEquals()
	{
		$data = new Blackbox_DataTestObj(array('test' => 10));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('VendorAPI_Blackbox_Rule_GreaterThanEquals', array('hitStat', 'hitEvent'), array($this->event_log));
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'test',
			Blackbox_StandardRule::PARAM_VALUE  => 10,
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertTrue($v);
	}

	/**
	 * Test for a value that's less than the rule; should return FALSE
	 *
	 * @return void
	 */
	public function testLess()
	{
		$data = new Blackbox_DataTestObj(array('test' => 5));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('VendorAPI_Blackbox_Rule_GreaterThanEquals', array('hitStat', 'hitEvent'), array($this->event_log));
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'test',
			Blackbox_StandardRule::PARAM_VALUE  => 10,
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertFalse($v);
	}
}
?>
