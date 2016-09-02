<?php
/**
 * LessThanEqualsTest PHPUnit test file.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */

require_once('blackbox_test_setup.php');

/**
 * PHPUnit test class for the Blackbox_Rule_LessThanEquals class.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class Blackbox_Rule_LessThanEqualsTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test for a value that's greater than the rule; should return TRUE
	 *
	 * @return void
	 */
	public function testGreater()
	{
		$data = new Blackbox_DataTestObj(array('test' => 30));
		$state_data = new Blackbox_StateData();

		$rule = new Blackbox_Rule_LessThanEquals();
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'test',
			Blackbox_StandardRule::PARAM_VALUE  => 20,
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertFalse($v);
	}

	/**
	 * Test for a value that's equal to the rule; should return TRUE
	 *
	 * @return void
	 */
	public function testEquals()
	{
		$data = new Blackbox_DataTestObj(array('test' => 20));
		$state_data = new Blackbox_StateData();

		$rule = new Blackbox_Rule_LessThanEquals();
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'test',
			Blackbox_StandardRule::PARAM_VALUE  => 20,
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
		$data = new Blackbox_DataTestObj(array('test' => 10));
		$state_data = new Blackbox_StateData();

		$rule = new Blackbox_Rule_LessThanEquals();
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => 'test',
			Blackbox_StandardRule::PARAM_VALUE  => 20,
		));

		$v = $rule->isValid($data, $state_data);
		$this->assertTrue($v);
	}
}
?>
