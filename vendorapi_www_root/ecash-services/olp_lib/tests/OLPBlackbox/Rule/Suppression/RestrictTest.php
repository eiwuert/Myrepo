<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test class for OLPBlackbox_Rule_Suppression_Restrict.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_Suppression_RestrictTest extends OLPBlackbox_Rule_Suppression_Base
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
	 * Data provider for the testIsValid function.
	 *
	 * @return array
	 */
	public static function isValidDataProvider()
	{
		return array(
			array(TRUE, OLPBlackbox_Config::EVENT_RESULT_PASS, TRUE), // matches the list, pass the rule
			array(FALSE, OLPBlackbox_Config::EVENT_RESULT_FAIL, FALSE)  // doesn't match the list, fail the rule
		);
	}

	/**
	 * Tests that run rule returns the correct value depending on the list Match method.
	 *
	 * @param bool $list_return the value Match will return
	 * @param string $expected_result the expected result
	 * @param bool $expected_valid the expected value of isValid
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValid($list_return, $expected_result, $expected_valid)
	{
		// Using this for the test of failure reasons.
		$state_data = $this->getFailureReasonsState();
		
		// Setup the suppression list
		$list = $this->getMock('Suppress_List', array(), array(NULL, NULL));
		$list->expects($this->any())->method('Match')->will($this->returnValue($list_return));
		
		$rule = $this->getMock(
			'OLPBlackbox_Rule_Suppression_Restrict',
			array('canRun', 'hitRuleEvent', 'hitEvent', 'hitStat'),
			array($list)
		);
		$rule->expects($this->once())->method('canRun')->will($this->returnValue(TRUE));
		
		$valid = $rule->isValid($this->bb_data, $state_data);
		
		$this->assertEquals($valid, $expected_valid);
		
		// if the rule is not valid, make sure the failure reason is recorded
		if (!$valid) 
		{
			$this->assertFalse($state_data->failure_reasons->isEmpty());
		}
	}
	
	/**
	 * Tests that run rule returns the correct value depending on the list Match method, including
	 * checking the cache.
	 *
	 * @param bool $list_return    the value Match will return
	 * @param string $expected_result the expected result
	 * @param bool $expected_valid the expected value of isValid
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValidCache($list_return, $expected_result, $expected_valid)
	{
		// Setup the suppression list
		$list = $this->getMock('Suppress_List', array(), array(NULL, NULL));
		$list->expects($this->once())->method('Match')->will($this->returnValue($list_return));
		
		$rule = $this->getMock(
			'OLPBlackbox_Rule_Suppression_Restrict',
			array('canRun', 'hitRuleEvent', 'hitEvent', 'hitStat'),
			array($list)
		);
		$rule->expects($this->exactly(2))->method('canRun')->will($this->returnValue(TRUE));
		
		$valid = $rule->isValid($this->bb_data, $this->state_data);
		$this->assertEquals($valid, $expected_valid);
		
		$valid = $rule->isValid($this->bb_data, $this->state_data);
		$this->assertEquals($valid, $expected_valid);
	}
}
?>
