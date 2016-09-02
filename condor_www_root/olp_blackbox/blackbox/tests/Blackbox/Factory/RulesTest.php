<?php
/**
 * Blackbox_Factory_RuleTest PHPUnit test file.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

require_once('blackbox_test_setup.php');

/**
 * PHPUnit test class for the default Rule factory class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Factory_RulesTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that the getRule function in the Blackbox_Factory_Rules, returns the correct generic
	 * Blackbox rules.
	 *
	 * @return void
	 */
	public function testGetRule()
	{
		$rule = Blackbox_Factory_Rules::getRule('Equals');
		$this->assertType('Blackbox_Rule_Equals', $rule);

		$rule = Blackbox_Factory_Rules::getRule('GreaterThan');
		$this->assertType('Blackbox_Rule_GreaterThan', $rule);

		$rule = Blackbox_Factory_Rules::getRule('GreaterThanEquals');
		$this->assertType('Blackbox_Rule_GreaterThanEquals', $rule);

		$rule = Blackbox_Factory_Rules::getRule('In');
		$this->assertType('Blackbox_Rule_In', $rule);

		$rule = Blackbox_Factory_Rules::getRule('LessThan');
		$this->assertType('Blackbox_Rule_LessThan', $rule);

		$rule = Blackbox_Factory_Rules::getRule('LessThanEquals');
		$this->assertType('Blackbox_Rule_LessThanEquals', $rule);

		$rule = Blackbox_Factory_Rules::getRule('NotIn');
		$this->assertType('Blackbox_Rule_NotIn', $rule);
	}

	/**
	 * Ensure that we get an exception with an invalid rule name
	 *
	 * @expectedException InvalidArgumentException
	 * @return void
	 */
	public function testInvalidRule()
	{
		Blackbox_Factory_Rules::getRule('THISRULESHOULDNEVEREVEREXIST');
	}

	/**
	 * Runs a test to check that when creating two rules with the same name parameter, that they
	 * create two different objects.
	 *
	 * @return void
	 */
	public function testSameRuleParameters()
	{
		$rule_field = 'minimum_income';

		$params = array(
			Blackbox_StandardRule::PARAM_FIELD => $rule_field,
			Blackbox_StandardRule::PARAM_VALUE => 3000
		);

		$params2 = array(
			Blackbox_StandardRule::PARAM_FIELD => $rule_field,
			Blackbox_StandardRule::PARAM_VALUE => 500
		);

		$first_rule = Blackbox_Factory_Rules::getRule('GreaterThan', $params);
		$second_rule = Blackbox_Factory_Rules::getRule('GreaterThan', $params2);

		$this->assertNotSame($first_rule, $second_rule);
	}
}
?>
