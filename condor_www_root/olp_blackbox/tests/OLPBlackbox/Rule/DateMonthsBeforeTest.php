<?php
/**
 * DateBefore PHPUnit test file.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Rule_DateMonthsBefore class.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */
class OLPBlackbox_Rule_DateMonthsBeforeTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test for a value that's before the rule; should return TRUE
	 *
	 * @return void
	 */
	public function testBefore()
	{
		$data = new Blackbox_DataTestObj(array('test' => date('Y-m-d', strtotime('-9 months'))));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_DateMonthsBefore', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
			OLPBlackbox_Rule::PARAM_FIELD => 'test',
			OLPBlackbox_Rule::PARAM_VALUE  => 6,
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
		$data = new Blackbox_DataTestObj(array('test' => date('Y-m-d', strtotime('-6 months'))));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_DateMonthsBefore', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
			OLPBlackbox_Rule::PARAM_FIELD => 'test',
			OLPBlackbox_Rule::PARAM_VALUE  => 6,
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertTrue($v);
	}

	/**
	 * Test for a value that's after the rule; should return FALSE
	 *
	 * @return void
	 */
	public function testAfter()
	{
		$data = new Blackbox_DataTestObj(array('test' => date('Y-m-d', strtotime('-3 months'))));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_DateMonthsBefore', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
			OLPBlackbox_Rule::PARAM_FIELD => 'test',
			OLPBlackbox_Rule::PARAM_VALUE  => 6,
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertFalse($v);
	}
}
?>
