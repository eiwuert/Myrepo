<?php
/**
 * EqualsTest PHPUnit test file.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

require_once('blackbox_test_setup.php');

/**
 * PHPUnit test class for the Blackbox_Rule_Equals class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Rule_EqualsTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test for a value that's equal to the rule; should return TRUE
	 *
	 * @return void
	 */
	public function testEqual()
	{
			$data = new Blackbox_DataTestObj(array('test' => 'woot'));
			$state_data = new Blackbox_StateData();

			$rule = new Blackbox_Rule_Equals();
			$rule->setupRule(array(
				Blackbox_StandardRule::PARAM_FIELD => 'test',
				Blackbox_StandardRule::PARAM_VALUE  => 'woot',
			));

			$v = $rule->isValid($data, $state_data);
			$this->assertTrue($v);
	}

	/**
	 * Test for a value that is not equal to the rule; should return FALSE
	 *
	 * @return void
	 */
	public function testNotEqual()
	{
			$data = new Blackbox_DataTestObj(array('test' => 'woot'));
			$state_data = new Blackbox_StateData();

			$rule = new Blackbox_Rule_Equals();
			$rule->setupRule(array(
				Blackbox_StandardRule::PARAM_FIELD => 'test',
				Blackbox_StandardRule::PARAM_VALUE  => 'blah',
			));

			$v = $rule->isValid($data, $state_data);
			$this->assertFalse($v);
	}
}
?>
