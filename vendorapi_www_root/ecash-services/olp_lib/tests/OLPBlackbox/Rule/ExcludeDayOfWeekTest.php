<?php
/**
 * ExcludeDayOfWeekTest PHPUnit test file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Rule_ExcludeDayOfWeek class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Rule_ExcludeDayOfWeekTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Setup function for each test.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->utils = new Blackbox_Utils();
	}

	/**
	 * Tear Down function for each test.
	 *
	 * @return void
	 */
	public function tearDown()
	{
		$this->utils->resetToday();
	}

	/**
	 * Make sure when "getToday" returns a day of the week that is in the
	 * param array, the rule fails; should return FALSE
	 *
	 * @return void
	 */
	public function testDateIn()
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();

		$this->utils->setToday('2008-01-05');

		$rule = $this->getMock('OLPBlackbox_Rule_ExcludeDayOfWeek', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_VALUE => array('sat','sun')
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertFalse($v);
	}

	/**
	 * Make sure when "getToday" returns a day of the week that is not in the
	 * param array, the rule passes; should return TRUE
	 *
	 * @return void
	 */
	public function testExcludeDayOfWeek()
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();

		$this->utils->setToday('2008-01-01');

		$rule = $this->getMock('OLPBlackbox_Rule_ExcludeDayOfWeek', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_VALUE => array('sat')
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertTrue($v);
	}

	/**
	 * Make sure when we dont pass in a valid array of dates the rule
	 * is skipped; should return NULL
	 *
	 * @return void
	 */
	public function testExcludeDayOfWeekNoArray()
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_ExcludeDayOfWeek', array('onError', 'hitEvent', 'hitStat'));
		$rule->expects($this->once())->method('onError');
		$rule->setupRule(array(
					Blackbox_StandardRule::PARAM_VALUE => 'NotAnArray',
					));

		$v = $rule->isValid($data, $state_data);
		$this->assertEquals(NULL, $v);
	}

}

?>
