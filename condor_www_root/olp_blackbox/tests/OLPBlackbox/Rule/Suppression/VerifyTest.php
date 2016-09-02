<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test class for OLPBlackbox_Rule_Suppression_Verify.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_Suppression_VerifyTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data object to test with.
	 *
	 * @var OLPBlackbox_Data
	 */
	protected $bb_data;
	
	/**
	 * State Data object to test with.
	 *
	 * @var Blackbox_StateData
	 */
	protected $state_data;
	
	/**
	 * Setup function for each test.
	 * 
	 * @return void
	 */
	public function setUp()
	{
		$this->bb_data    = new OLPBlackbox_Data();
		$this->state_data = new Blackbox_StateData();
	}
	
	/**
	 * Data provider for the testRunRule function.
	 *
	 * @return array
	 */
	public static function runRuleDataProvider()
	{
		return array(
			array(TRUE, TRUE, 'VERIFY'),   // matches the list, pass the rule, verify the result
			array(FALSE, TRUE, 'VERIFIED') // doesn't match the list, pass the rule, verified result
		);
	}
	
	/**
	 * Tests that run rule returns the correct value depending on the list Match method.
	 *
	 * @param bool $list_return     the value Match will return
	 * @param bool $expected_valid  the expected value of runRule
	 * @param bool $expected_result the expected result of the list
	 * @dataProvider runRuleDataProvider
	 * @return void
	 */
	public function testRunRule($list_return, $expected_valid, $expected_result)
	{
		// Setup the suppression list
		$list = $this->getMock('Suppress_List', array(), array(NULL, NULL));
		$list->expects($this->any())->method('Match')->will($this->returnValue($list_return));
		
		$rule = $this->getMock(
			'OLPBlackbox_Rule_Suppression_Verify',
			array('canRun', 'hitEvent', 'hitStat'),
			array($list)
		);
		$rule->expects($this->once())->method('canRun')->will($this->returnValue(TRUE));
		
		$valid = $rule->isValid($this->bb_data, $this->state_data);
		
		$this->assertEquals($expected_valid, $valid);
		$this->assertEquals($expected_result, $rule->getResult());
	}
	
	/**
	 * Tests that run rule returns the correct value depending on the list Match method, including
	 * checking the cache.
	 *
	 * @param bool   $list_return     the value Match will return
	 * @param bool   $expected_valid  the expected value of runRule
	 * @param string $expected_result the string we expect to get back as a result
	 * @dataProvider runRuleDataProvider
	 * @return void
	 */
	public function testRunRuleCache($list_return, $expected_valid, $expected_result)
	{
		// Setup the suppression list
		$list = $this->getMock('Suppress_List', array(), array(NULL, NULL));
		$list->expects($this->once())->method('Match')->will($this->returnValue($list_return));
		
		$rule = $this->getMock('OLPBlackbox_Rule_Suppression_Verify', array('canRun', 'hitStat', 'hitEvent'), array($list));
		$rule->expects($this->exactly(2))->method('canRun')->will($this->returnValue(TRUE));
		
		$valid = $rule->isValid($this->bb_data, $this->state_data);
		$this->assertEquals($expected_valid, $valid);
		$this->assertEquals($expected_result, $rule->getResult());
		
		$valid = $rule->isValid($this->bb_data, $this->state_data);
		$this->assertEquals($expected_valid, $valid);
		$this->assertEquals($expected_result, $rule->getResult());
	}
}
?>
