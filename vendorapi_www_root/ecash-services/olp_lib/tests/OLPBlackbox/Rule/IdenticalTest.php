<?php
/**
 * IdenticalTest PHPUnit test file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Rule_Identical class.
 *
  * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Rule_IdenticalTest extends PHPUnit_Framework_TestCase
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

			$rule = $this->getMock('OLPBlackbox_Rule_Identical', array('hitStat', 'hitEvent'));
			$rule->setupRule(array(
				OLPBlackbox_Rule::PARAM_FIELD => 'test',
				OLPBlackbox_Rule::PARAM_VALUE  => TRUE,
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

			$rule = $this->getMock('OLPBlackbox_Rule_Identical', array('hitStat', 'hitEvent'));
			$rule->setupRule(array(
				OLPBlackbox_Rule::PARAM_FIELD => 'test',
				OLPBlackbox_Rule::PARAM_VALUE  => TRUE,
			));

			$v = $rule->isValid($data, $state_data);
			$this->assertFalse($v);
	}
}
?>
