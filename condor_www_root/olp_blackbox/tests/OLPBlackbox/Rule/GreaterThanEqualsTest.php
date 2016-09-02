<?php
/**
 * EqualsTest PHPUnit test file.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Rule_Equals class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_GreaterThanEqualsTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test for a value that's greater than the rule; should return TRUE
	 *
	 * @return void
	 */
	public function testGreater()
	{
		$data = new Blackbox_DataTestObj(array('test' => 20));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_GreaterThanEquals', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
			OLPBlackbox_Rule::PARAM_FIELD => 'test',
			OLPBlackbox_Rule::PARAM_VALUE  => 10,
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

		$rule = $this->getMock('OLPBlackbox_Rule_GreaterThanEquals', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
			OLPBlackbox_Rule::PARAM_FIELD => 'test',
			OLPBlackbox_Rule::PARAM_VALUE  => 10,
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

		$rule = $this->getMock('OLPBlackbox_Rule_GreaterThanEquals', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
			OLPBlackbox_Rule::PARAM_FIELD => 'test',
			OLPBlackbox_Rule::PARAM_VALUE  => 10,
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertFalse($v);
	}
}
?>
