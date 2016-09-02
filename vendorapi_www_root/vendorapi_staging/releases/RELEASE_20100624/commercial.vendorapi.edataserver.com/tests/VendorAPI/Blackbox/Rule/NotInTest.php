<?php

/**
 * Unit tests for the VendorAPI_Blackbox_Rule_NotIn object
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_NotInTest extends PHPUnit_Framework_TestCase
{
	protected $event_log = "";
	
	public function setUp()
	{
		$this->event_log = $this->getMock("VendorAPI_Blackbox_EventLog", array(), array(), '', FALSE);
	}
	
	/**
	 * Test for a value that exists; should return FALSE
	 *
	 * @return void
	 */
	public function testInExists()
	{
		$data = new Blackbox_DataTestObj(array('test' => 'woot'));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('VendorAPI_Blackbox_Rule_NotIn', array('hitStat', 'hitEvent'), array($this->event_log));
		$rule->setupRule(array(
					Blackbox_StandardRule::PARAM_FIELD => 'test',
					Blackbox_StandardRule::PARAM_VALUE  => array('woot'),
					));

		$v = $rule->isValid($data, $state_data);
		$this->assertFalse($v);
	}

	/**
	 * Test for a value that doesn't exist; should return TRUE
	 *
	 * @return void
	 */
	public function testInNotExists()
	{
		$data = new Blackbox_DataTestObj(array('test' => 'woot'));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('VendorAPI_Blackbox_Rule_NotIn', array('hitStat', 'hitEvent'), array($this->event_log));
		$rule->setupRule(array(
					Blackbox_StandardRule::PARAM_FIELD => 'test',
					Blackbox_StandardRule::PARAM_VALUE  => array('blah'),
					));

		$v = $rule->isValid($data, $state_data);
		$this->assertTrue($v);
	}

	/**
	 * Test for a param_value that's not an array; should get an exception
	 *
	 * @return void
	 */
	public function testNotArray()
	{
		$data = new Blackbox_DataTestObj(array('test' => 'woot'));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('VendorAPI_Blackbox_Rule_NotIn', array('onError', 'hitEvent', 'hitStat'), array($this->event_log));
		$rule->expects($this->once())->method('onError');
		$rule->setupRule(array(
					Blackbox_StandardRule::PARAM_FIELD => 'test',
					Blackbox_StandardRule::PARAM_VALUE  => 'NOTANARRAYHAHAHAHAHA',
					));

		$v = $rule->isValid($data, $state_data);
		$this->assertEquals(NULL, $v);
	}
}

?>
