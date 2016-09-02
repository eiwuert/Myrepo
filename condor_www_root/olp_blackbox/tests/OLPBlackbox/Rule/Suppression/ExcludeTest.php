<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test class for OLPBlackbox_Rule_Suppression_Exclude.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_Suppression_ExcludeTest extends PHPUnit_Framework_TestCase
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
			array(TRUE, FALSE), // matches the list, fail the rule
			array(FALSE, TRUE)  // doesn't match the list, pass the rule
		);
	}

	/**
	 * Tests that run rule returns the correct value depending on the list Match method.
	 *
	 * @param bool $list_return    the value Match will return
	 * @param bool $expected_valid the expected value of isValid
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValid($list_return, $expected_valid)
	{
		// Setup the suppression list
		$list = $this->getMock('Suppress_List', array(), array(NULL, NULL));
		$list->expects($this->any())->method('Match')->will($this->returnValue($list_return));
		
		$rule = $this->getMock('OLPBlackbox_Rule_Suppression_Exclude', array('canRun', 'hitEvent', 'hitStat'), array($list));
		$rule->expects($this->any())->method('canRun')->will($this->returnValue(TRUE));
		
		$valid = $rule->isValid($this->bb_data, $this->state_data);
		
		$this->assertEquals($valid, $expected_valid);
	}
	
	/**
	 * Tests that run rule returns the correct value depending on the list Match method, including
	 * checking the cache.
	 *
	 * @param bool $list_return    the value Match will return
	 * @param bool $expected_valid the expected value of isValid
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValidCache($list_return, $expected_valid)
	{
		// Setup the suppression list
		$list = $this->getMock('Suppress_List', array(), array(NULL, NULL));
		$list->expects($this->once())->method('Match')->will($this->returnValue($list_return));
		
		$rule = $this->getMock('OLPBlackbox_Rule_Suppression_Exclude', array('canRun', 'hitEvent', 'hitStat'), array($list));
		$rule->expects($this->any())->method('canRun')->will($this->returnValue(TRUE));
		
		$valid = $rule->isValid($this->bb_data, $this->state_data);
		$this->assertEquals($valid, $expected_valid);
		
		$valid = $rule->isValid($this->bb_data, $this->state_data);
		$this->assertEquals($valid, $expected_valid);
	}
}
?>
