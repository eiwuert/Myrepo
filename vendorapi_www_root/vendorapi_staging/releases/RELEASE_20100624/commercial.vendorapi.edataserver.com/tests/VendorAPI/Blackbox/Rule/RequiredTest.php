<?php
/**
 * PHPUnit test class for the VendorAPI_Blackbox_Rule_Required class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_RequiredTest extends PHPUnit_Framework_TestCase
{
	protected $event_log = "";
	
	public function setUp()
	{
		$this->event_log = $this->getMock("VendorAPI_Blackbox_EventLog", array(), array(), '', FALSE);
	}
	
	/**
	 * Test for a required value that exists; should return TRUE
	 *
	 * @return void
	 */
	public function testRequiredExists()
	{
			$data = new Blackbox_DataTestObj(array('test' => 'woot'));
			$state_data = new Blackbox_StateData();

			$rule = $this->getMock('VendorAPI_Blackbox_Rule_Required', array('hitStat', 'hitEvent'), array($this->event_log));
			$rule->setupRule(array(
				Blackbox_StandardRule::PARAM_FIELD => 'test',
			));
			
			$v = $rule->isValid($data, $state_data);
			$this->assertTrue($v);
	}
	
	/**
	 * Test for a required value that exists but is null; should return FALSE
	 *
	 * @return void
	 */
	public function testRequiredExistsButNull()
	{
			$data = new Blackbox_DataTestObj(array('test' => ''));
			$state_data = new Blackbox_StateData();

			$rule = $this->getMock('VendorAPI_Blackbox_Rule_Required', array('hitStat', 'hitEvent'), array($this->event_log));
			$rule->setupRule(array(
				Blackbox_StandardRule::PARAM_FIELD => 'test',
			));
			
			$v = $rule->isValid($data, $state_data);
			$this->assertFalse($v);
	}
	
	/**
	 * Test for a required value that doesnt exist; should return FALSE
	 *
	 * @return void
	 */
	public function testRequiredNotExists()
	{
			$data = new Blackbox_DataTestObj(array('not_test' => 'woot'));
			$state_data = new Blackbox_StateData();

			$rule = $this->getMock('VendorAPI_Blackbox_Rule_Required', array('hitStat', 'hitEvent'), array($this->event_log));
			$rule->setupRule(array(
				Blackbox_StandardRule::PARAM_FIELD => 'test',
			));

			$v = $rule->isValid($data, $state_data);
			$this->assertFalse($v);
	}
}
?>
