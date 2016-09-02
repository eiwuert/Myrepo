<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test class for OLPBlackbox_Rule_Suppression_Catch.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_Suppression_CatchTest extends OLPBlackbox_Rule_Suppression_Base
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
		$this->state_data = new OLPBlackbox_TargetStateData();
	}
	
	/**
	 * Data provider for the testRunRule function.
	 *
	 * @return array
	 */
	public static function runRuleDataProvider()
	{
		return array(
			array(
				TRUE,
				FALSE,
				OLPBlackbox_Rule_Suppression_Catch::CAUGHT
			), // matches the list, fail the rule, CAUGHT result
			array(
				FALSE,
				TRUE,
				OLPBlackbox_Rule_Suppression_Catch::MISS
			)    // doesn't match the list, pass the rule, MISS result
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
		$state_data = $this->getFailureReasonsState();
		
		// Setup the suppression list
		$list = $this->getMock('Suppress_List', array(), array(NULL, NULL));
		$list->expects($this->any())->method('Match')->will($this->returnValue($list_return));
		
		$rule = $this->getMock('OLPBlackbox_Rule_Suppression_Catch', array('canRun', 'hitEvent', 'hitStat'), array($list));
		$rule->expects($this->any())->method('canRun')->will($this->returnValue(TRUE));
		$valid = $rule->isValid($this->bb_data, $state_data);
		
		$this->assertEquals($valid, $expected_valid);
		$this->assertEquals($rule->getResult(), $expected_result);
		if (!$valid)
		{
			$this->assertFalse($state_data->failure_reasons->isEmpty());
		}
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
		
		$rule = $this->getMock('OLPBlackbox_Rule_Suppression_Catch', array('canRun', 'hitEvent', 'hitStat'), array($list));
		$rule->expects($this->any())->method('canRun')->will($this->returnValue(TRUE));
		
		$valid = $rule->isValid($this->bb_data, $this->state_data);
		$this->assertEquals($expected_valid, $valid);
		$this->assertEquals($expected_result, $rule->getResult());
		
		$valid = $rule->isValid($this->bb_data, $this->state_data);
		$this->assertEquals($expected_valid, $valid);
		$this->assertEquals($expected_result, $rule->getResult());
	}
	
	/**
	 * Tests that we save the state data correctly when running the CATCH list.
	 *
	 * @todo Add more test data cases
	 * @return void
	 */
	public function testRunRuleStateSave()
	{
		// We expect this array in the suppression_lists section of the state data
		$expected_array = array(
			'CATCH' => array(
				'vs' => array(
					'store' => array(
						'ref' => 5102,
						'desc' => 'Just a test'
					)
				)
			)
		);
		
		// Setup the suppression list
		$list = $this->getMock('Suppress_List', array(), array(NULL, NULL));
		$list->expects($this->once())->method('Match')->will($this->returnValue(TRUE));
		$list->expects($this->once())->method('Name')->will($this->returnValue('vs_STORE_5102'));
		$list->expects($this->once())->method('Description')
			->will($this->returnValue('Just a test'));
		
		$rule = $this->getMock('OLPBlackbox_Rule_Suppression_Catch', array('canRun', 'hitEvent', 'hitStat'), array($list));
		$rule->expects($this->any())->method('canRun')->will($this->returnValue(TRUE));
		
		$valid = $rule->isValid($this->bb_data, $this->state_data);
		$this->assertFalse($valid);
		$this->assertEquals('CAUGHT', $rule->getResult());
		$this->assertEquals($expected_array, $this->state_data->suppression_lists);
	}
}
?>
