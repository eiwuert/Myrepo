<?php
/**
 * MinimumAgeTest PHPUnit test file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Rule_MinimumAge class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Rule_MinimumAgeTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test that a dob is greater than 18 years
	 * Expected Result: TRUE
	 *
	 * @return void
	 */
	public function testGreaterThanAge()
	{
		$data = new OLPBlackbox_Data();
		$data->dob = '01/01/1950';
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_MinimumAge', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'dob',
			Blackbox_StandardRule::PARAM_VALUE => '18',
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertTrue($v);
	}

	/**
	 * Test that a dob is not greater than 18 years
	 * Expected Result: FALSE
	 *
	 * @return void
	 */
	public function testLessThanAge()
	{
		$data = new OLPBlackbox_Data();
		$data->dob = '01/01/2030';
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_MinimumAge', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'dob',
			Blackbox_StandardRule::PARAM_VALUE => '18',
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertFalse($v);
	}

	/**
	 * Test that a missing dob causes the rule to be skipped.
	 * Expected Result: NULL
	 *
	 * @return void
	 */
	public function testNoDobException()
	{
		$data = new Blackbox_DataTestObj(array('no_dob' => 'hahaha'));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_MinimumAge', array('onSkip', 'hitEvent', 'hitStat'));
		$rule->expects($this->once())->method('onSkip');
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'dob',
			Blackbox_StandardRule::PARAM_VALUE => '18',
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertEquals(NULL, $v);
	}

	/**
	 * Test that an invalid age throws an exception.
	 * Expected Result: NULL
	 *
	 * @return void
	 */
	public function testAgeException()
	{
		$data = new OLPBlackbox_Data();
		$data->dob = '01/01/1950';
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_MinimumAge', array('onError', 'hitEvent', 'hitStat'));
		$rule->expects($this->once())->method('onError');
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'dob',
			Blackbox_StandardRule::PARAM_VALUE => 'a', // Invalid age that should trigger exception
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertEquals(NULL, $v);
	}

	/**
	 * Test that an invalid dob format throws an exception.
	 * Expected Result: NULL
	 *
	 * @return void
	 */
	public function testDobException()
	{
		$data = new OLPBlackbox_Data();
		$data->dob = 'no_dob';
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_MinimumAge', array('onError', 'hitEvent', 'hitStat'));
		$rule->expects($this->once())->method('onError');
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'dob',
			Blackbox_StandardRule::PARAM_VALUE => '18',
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertEquals(NULL, $v);
	}

	/**
	 * Check to make sure someone turning 18 tomorrow isnt miscalculated.
	 * Expected Result: FALSE
	 *
	 * @return void
	 */
	public function testAgeMetTomorrow()
	{
		$dob = strtotime("-18 years +1 day");
		$data = new OLPBlackbox_Data();
		$data->dob = date("m/d/Y", $dob);
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_MinimumAge', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'dob',
			Blackbox_StandardRule::PARAM_VALUE => '18',
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertFalse($v);
	}

	/**
	 * Check to make sure someone who turned 18 yesterday is calculated correct.
	 * Expected Result: TRUE
	 *
	 * @return void
	 */
	public function testAgeMetYesterday()
	{
		$dob = strtotime("-18 years -1 day");
		$data = new OLPBlackbox_Data();
		$data->dob = date("m/d/Y", $dob);
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_MinimumAge', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'dob',
			Blackbox_StandardRule::PARAM_VALUE => '18',
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertTrue($v);
	}

	/**
	 * Check to make sure an age of zero does not run the rule.
	 * Expected Result: TRUE
	 *
	 * @return void
	 */
	public function testAgeIsZeroSoDontRun()
	{
		$data = new OLPBlackbox_Data();
		$data->dob = '01/01/2030';
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_MinimumAge', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'dob',
			Blackbox_StandardRule::PARAM_VALUE => '0', // Zero age, rule should skip.
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertTrue($v);
	}

}
?>
