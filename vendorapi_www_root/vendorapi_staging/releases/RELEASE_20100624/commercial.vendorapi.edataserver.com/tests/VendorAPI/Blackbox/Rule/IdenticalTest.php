<?php

/**
 * PHPUnit test class for the VendorAPI_Blackbox_Rule_IdenticalTest class.
 *
  * @author Matt Piper <matt.piper@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_IdenticalTest extends PHPUnit_Framework_TestCase
{
	protected $event_log = "";
	
	public function setUp()
	{
		$this->event_log = $this->getMock("VendorAPI_Blackbox_EventLog", array(), array(), '', FALSE);
	}
	
	/**
	 * Test for a values that are identical; should return TRUE
	 *
	 * @return void
	 */
	public function testIdentical()
	{
			$data = new Blackbox_DataTestObj(array('test' => TRUE));
			$state_data = new Blackbox_StateData();

			$rule = $this->getMock('VendorAPI_Blackbox_Rule_Identical', array('hitStat', 'hitEvent'), array($this->event_log));
			$rule->setupRule(array(
				Blackbox_StandardRule::PARAM_FIELD => 'test',
				Blackbox_StandardRule::PARAM_VALUE  => TRUE,
			));

			$v = $rule->isValid($data, $state_data);
			$this->assertTrue($v);
	}
	
	/**
	 * Test for a values that are identical; should return FALSE
	 *
	 * @return void
	 */
	public function testNotIdentical()
	{
			$data = new Blackbox_DataTestObj(array('test' => 'TRUE'));
			$state_data = new Blackbox_StateData();

			$rule = $this->getMock('VendorAPI_Blackbox_Rule_Identical', array('hitStat', 'hitEvent'), array($this->event_log));
			$rule->setupRule(array(
				Blackbox_StandardRule::PARAM_FIELD => 'test',
				Blackbox_StandardRule::PARAM_VALUE  => TRUE,
			));

			$v = $rule->isValid($data, $state_data);
			$this->assertFalse($v);
	}
}
?>
