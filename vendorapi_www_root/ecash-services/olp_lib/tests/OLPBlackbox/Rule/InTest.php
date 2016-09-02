<?php

require_once 'OLPBlackboxTestSetup.php';

/**
 * Unit tests for the OLPBlackbox_Rule_In object
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Rule_InTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test for a value that exists; should return TRUE
	 *
	 * @return void
	 */
	public function testInExists()
	{
		$data = new Blackbox_DataTestObj(array('test' => 'woot'));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_In', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
					OLPBlackbox_Rule::PARAM_FIELD => 'test',
					OLPBlackbox_Rule::PARAM_VALUE  => array('woot'),
					));

		$v = $rule->isValid($data, $state_data);
		$this->assertTrue($v);
	}

	/**
	 * Test for a value that doesn't exist; should return FALSE
	 *
	 * @return void
	 */
	public function testInNotExists()
	{
		$data = new Blackbox_DataTestObj(array('test' => 'woot'));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_In', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
					OLPBlackbox_Rule::PARAM_FIELD => 'test',
					OLPBlackbox_Rule::PARAM_VALUE  => array('blah'),
					));

		$v = $rule->isValid($data, $state_data);
		$this->assertFalse($v);
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

		// when we call isValid, the runRule() method will throw an exception due to
		// illegal parameters in setupRule(), so make sure onError is called.
		$rule = $this->getMock('OLPBlackbox_Rule_In', array('onError', 'hitEvent', 'hitStat'));
		$rule->expects($this->once())->method('onError');
		$rule->setupRule(array(
					OLPBlackbox_Rule::PARAM_FIELD => 'test',
					OLPBlackbox_Rule::PARAM_VALUE  => 'NOTANARRAYHAHAHAHAHA',
					));

		$v = $rule->isValid($data, $state_data);
		$this->assertEquals(NULL, $v);
	}
}

?>
