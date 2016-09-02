<?php
/**
 * IdenticalTest PHPUnit test file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

require_once('blackbox_test_setup.php');

/**
 * PHPUnit test class for the Blackbox_Rule_Identical class.
 *
  * @author Matt Piper <matt.piper@sellingsource.com>
 */
class Blackbox_Rule_IdenticalTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test for a values that are identical; should return TRUE
	 *
	 * @return void
	 */
	public function testIdentical()
	{
			$data = new Blackbox_DataTestObj(array('test' => TRUE));
			$state_data = new Blackbox_StateData();

			$rule = new Blackbox_Rule_Identical();
			$rule->setupRule(array(
				Blackbox_StandardRule::PARAM_FIELD => 'test',
				Blackbox_StandardRule::PARAM_VALUE  => TRUE,
			));

			$v = $rule->isValid($data, $state_data);
			$this->assertTrue($v);
	}

	/**
	 * Test for a values that are identical; should return FALSE
	 *
	 * @return void
	 */
	public function testNotIdentical()
	{
			$data = new Blackbox_DataTestObj(array('test' => 'TRUE'));
			$state_data = new Blackbox_StateData();

			$rule = new Blackbox_Rule_Identical();
			$rule->setupRule(array(
				Blackbox_StandardRule::PARAM_FIELD => 'test',
				Blackbox_StandardRule::PARAM_VALUE  => TRUE,
			));

			$v = $rule->isValid($data, $state_data);
			$this->assertFalse($v);
	}
}
?>
