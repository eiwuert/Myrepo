<?php

require_once 'blackbox_test_setup.php';

/**
 * Unit tests for the Blackbox_Rule_In object
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class Blackbox_Rule_InTest extends PHPUnit_Framework_TestCase
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

		$rule = new Blackbox_Rule_In();
		$rule->setupRule(array(
					Blackbox_StandardRule::PARAM_FIELD => 'test',
					Blackbox_StandardRule::PARAM_VALUE  => array('woot'),
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

		$rule = new Blackbox_Rule_In();
		$rule->setupRule(array(
					Blackbox_StandardRule::PARAM_FIELD => 'test',
					Blackbox_StandardRule::PARAM_VALUE  => array('blah'),
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
		$rule = $this->getMock('Blackbox_Rule_In', array('onError'));
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
