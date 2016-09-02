<?php
/**
 * MaximumAgeTest PHPUnit test file.
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Rule_MinimumAge class.
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class OLPBlackbox_Rule_MaximumAgeTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test that a dob less than the max age passes
	 * Expected Result: TRUE
	 *
	 * @return void
	 */
	public function testLessThanAge()
	{
		$data = new OLPBlackbox_Data();
		// Someone born today is not 88 years old so this should pass
		$data->dob = date('m/d/Y');
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_MaximumAge', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'dob',
			Blackbox_StandardRule::PARAM_VALUE => '88',
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertTrue($v);
	}

	/**
	 * Check to make sure someone who is exactly the max age passes
	 * Expected Result: TRUE
	 *
	 * @return void
	 */
	public function testIsMaxAge()
	{
		$dob = strtotime("-88 years");
		$data = new OLPBlackbox_Data();
		$data->dob = date("m/d/Y", $dob);
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_MaximumAge', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'dob',
			Blackbox_StandardRule::PARAM_VALUE => '88',
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertTrue($v);
	}

	/**
	 * Test that a dob is lmore than 88 years
	 * Expected Result: FALSE
	 *
	 * @return void
	 */
	public function testGreaterThanAge()
	{
		$data = new OLPBlackbox_Data();
		// Someone born in 1900 is over 88 years old, they should fail
		$data->dob = '01/01/1970';
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_MaximumAge', array('hitStat', 'hitEvent', 'onSkip'));
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'dob',
			Blackbox_StandardRule::PARAM_VALUE => '30',
		));
		$rule->expects($this->never())->method('onSkip');
		$v = $rule->isValid($data, $state_data);
		$this->assertFalse($v);
	}

	/**
	 * Test that a missing dob causes the rule to be skipped.
	 * Expected Result: NULL
	 *
	 * @return void
	 */
	public function testNoDobSkip()
	{
		$data = new Blackbox_DataTestObj(array('no_dob' => 'hahaha'));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_MaximumAge', array('onSkip', 'hitEvent', 'hitStat'));
		$rule->expects($this->once())->method('onSkip');
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'dob',
			Blackbox_StandardRule::PARAM_VALUE => '88',
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertEquals(NULL, $v);
	}

	/**
	 * Check to make sure someone who is over the max age tomorrow still passes
	 * Expected Result: TRUE
	 *
	 * @return void
	 */
	public function testAgeMetTomorrow()
	{
		$dob = strtotime("-89 years +1 day");
		$data = new OLPBlackbox_Data();
		$data->dob = date("m/d/Y", $dob);
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_MaximumAge', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'dob',
			Blackbox_StandardRule::PARAM_VALUE => '88',
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertTrue($v);
	}

	/**
	 * Check to make sure someone who is over the max age today fails
	 * Expected Result: FALSE
	 *
	 * @return void
	 */
	public function testAgeMetToday()
	{
		$dob = strtotime("-89 years");
		$data = new OLPBlackbox_Data();
		$data->dob = date("m/d/Y", $dob);
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_MaximumAge', array('hitStat', 'hitEvent', 'onSkip'));
		$rule->expects($this->never())->method('onSkip');
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'dob',
			Blackbox_StandardRule::PARAM_VALUE => '88',
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertFalse($v);
	}

	/**
	 * Check to make sure someone who is over the max age yesterday fails
	 * Expected Result: FALSE
	 *
	 * @return void
	 */
	public function testAgeMetYesterday()
	{
		$dob = strtotime("-89 years -1 day");
		$data = new OLPBlackbox_Data();
		$data->dob = date("m/d/Y", $dob);
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_MaximumAge', array('hitStat', 'hitEvent', 'onSkip'));
		$rule->expects($this->never())->method('onSkip');
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'dob',
			Blackbox_StandardRule::PARAM_VALUE => '88',
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertFalse($v);
	}
}
?>
