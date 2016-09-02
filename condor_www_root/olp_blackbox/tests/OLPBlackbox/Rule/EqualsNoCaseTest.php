<?php
/**
 * EqualsNoCaseTest PHPUnit test file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Rule_EqualsNoCase class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Rule_EqualsNoCaseTest extends PHPUnit_Framework_TestCase
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

			$rule = $this->getMock('OLPBlackbox_Rule_EqualsNoCase', array('hitStat', 'hitEvent'));
			$rule->setupRule(array(
				OLPBlackbox_Rule::PARAM_FIELD => 'test',
				OLPBlackbox_Rule::PARAM_VALUE  => 'woot',
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

			$rule = $this->getMock('OLPBlackbox_Rule_EqualsNoCase', array('hitStat', 'hitEvent'));
			$rule->setupRule(array(
				OLPBlackbox_Rule::PARAM_FIELD => 'test',
				OLPBlackbox_Rule::PARAM_VALUE  => 'woot',
			));

			$v = $rule->isValid($data, $state_data);
			$this->assertFalse($v);
	}
}
?>
