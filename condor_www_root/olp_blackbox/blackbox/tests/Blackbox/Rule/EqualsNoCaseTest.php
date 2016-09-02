<?php
/**
 * EqualsNoCaseTest PHPUnit test file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

require_once('blackbox_test_setup.php');

/**
 * PHPUnit test class for the Blackbox_Rule_EqualsNoCase class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class Blackbox_Rule_EqualsNoCaseTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test that two of the same string with different case match; should return TRUE
	 * wOOt == woot
	 *
	 * @return void
	 */
	public function testEqualsNoCaseSameStr()
	{
			$data = new Blackbox_DataTestObj(array('test' => 'wOOt'));
			$state_data = new Blackbox_StateData();

			$rule = new Blackbox_Rule_EqualsNoCase();
			$rule->setupRule(array(
				Blackbox_StandardRule::PARAM_FIELD => 'test',
				Blackbox_StandardRule::PARAM_VALUE  => 'woot',
			));

			$v = $rule->isValid($data, $state_data);
			$this->assertTrue($v);
	}

	/**
	 * Test that two different strings dont match; should return FALSE
	 * w00t != woot [zero zero, not ohh ohh]
	 *
	 * @return void
	 */
	public function testEqualsNoCaseDiffStr()
	{
			$data = new Blackbox_DataTestObj(array('test' => 'w00t'));
			$state_data = new Blackbox_StateData();

			$rule = new Blackbox_Rule_EqualsNoCase();
			$rule->setupRule(array(
				Blackbox_StandardRule::PARAM_FIELD => 'test',
				Blackbox_StandardRule::PARAM_VALUE  => 'woot',
			));

			$v = $rule->isValid($data, $state_data);
			$this->assertFalse($v);
	}
}
?>
