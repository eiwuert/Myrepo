<?php
/**
 * PHPUnit test class for the VendorAPI_Blackbox_Rule_EqualsNoCase class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OVendorAPI_Blackbox_Rule_EqualsNoCaseTest extends PHPUnit_Framework_TestCase
{
	protected $event_log = "";
	
	public function setUp()
	{
		$this->event_log = $this->getMock("VendorAPI_Blackbox_EventLog", array(), array(), '', FALSE);
	}
	
	/**
	 * Test that two of the same string with different case match; should return TRUE
	 * wOOt == woot
	 *
	 * @return void
	 */
	public function testEqualsNoCaseSameStr()
	{
			$data = new Blackbox_DataTestObj(array('test' => 'wOOt'));
			$state_data = new Blackbox_StateData();

			$rule = $this->getMock('VendorAPI_Blackbox_Rule_EqualsNoCase', array('hitStat', 'hitEvent'), array($this->event_log));
			$rule->setupRule(array(
				Blackbox_StandardRule::PARAM_FIELD => 'test',
				Blackbox_StandardRule::PARAM_VALUE  => 'woot',
			));

			$v = $rule->isValid($data, $state_data);
			$this->assertTrue($v);
	}

	/**
	 * Test that two different strings dont match; should return FALSE
	 * w00t != woot [zero zero, not ohh ohh]
	 *
	 * @return void
	 */
	public function testEqualsNoCaseDiffStr()
	{
			$data = new Blackbox_DataTestObj(array('test' => 'w00t'));
			$state_data = new Blackbox_StateData();

			$rule = $this->getMock('VendorAPI_Blackbox_Rule_EqualsNoCase', array('hitStat', 'hitEvent'), array($this->event_log));
			$rule->setupRule(array(
				Blackbox_StandardRule::PARAM_FIELD => 'test',
				Blackbox_StandardRule::PARAM_VALUE  => 'woot',
			));

			$v = $rule->isValid($data, $state_data);
			$this->assertFalse($v);
	}
}
?>
