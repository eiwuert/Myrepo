<?php

require_once 'OLPBlackboxTestSetup.php';

/**
 * Unit tests for the OLPBlackbox_Rule_NotCompare object
 * 
 * @group rules
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Rule_NotCompareTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Check that two of the same values do not match
	 * Expected Return: FALSE
	 *
	 * @return void
	 */
	public function testCompareTwoSame()
	{
		$data = new Blackbox_DataTestObj(array('param1'=>'woot1', 'param2'=>'woot1'));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_NotCompare', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
					OLPBlackbox_Rule::PARAM_FIELD => array('param1','param2'),
					));
		
		$v = $rule->isValid($data, $state_data);
		$this->assertFalse($v);
	}
	
	/**
	 * Check that three of the same values do not match
	 * Expected Return: FALSE
	 *
	 * @return void
	 */
	public function testCompareThreeSame()
	{
		$data = new Blackbox_DataTestObj(array('param1'=>'woot1', 'param2'=>'woot1', 'param3'=>'woot1'));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_NotCompare', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
					OLPBlackbox_Rule::PARAM_FIELD => array('param1','param2','param3'),
					));
		
		$v = $rule->isValid($data, $state_data);
		$this->assertFalse($v);
	}
	
	/**
	 * Check that two different values do not match
	 * Expected Return: TRUE
	 *
	 * @return void
	 */
	public function testCompareTwoDifferent()
	{
		$data = new Blackbox_DataTestObj(array('param1'=>'woot1', 'param2'=>'woot2'));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_NotCompare', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
					OLPBlackbox_Rule::PARAM_FIELD => array('param1','param2'),
					));
		
		$v = $rule->isValid($data, $state_data);
		$this->assertTrue($v);
	}
	
	/**
	 * Check that three different values do not match
	 * Expected Return: TRUE
	 *
	 * @return void
	 */
	public function testCompareThreeDifferent()
	{
		$data = new Blackbox_DataTestObj(array('param1'=>'woot1', 'param2'=>'woot2', 'param3'=>'woot3'));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_NotCompare', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
					OLPBlackbox_Rule::PARAM_FIELD => array('param1','param2','param3'),
					));
		
		$v = $rule->isValid($data, $state_data);
		$this->assertTrue($v);
	}
	
	/**
	 * Check that two of the same and one different values do not match
	 * Expected Return: TRUE
	 *
	 * @return void
	 */
	public function testCompareThreeNotSame()
	{
		$data = new Blackbox_DataTestObj(array('param1'=>'woot1', 'param2'=>'woot1', 'param3'=>'woot3'));
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('OLPBlackbox_Rule_NotCompare', array('hitStat', 'hitEvent'));
		$rule->setupRule(array(
					OLPBlackbox_Rule::PARAM_FIELD => array('param1','param2','param3'),
					));
		
		$v = $rule->isValid($data, $state_data);
		$this->assertTrue($v);
	}
		
	/**
	 * Test for a value that's not an array and doesnt exist
	 * Expected Result: NULL
	 *
	 * @return void
	 */
	public function testNotArrayDoesntExist()
	{
		$data = new Blackbox_DataTestObj(array('param1'=>'woot1'));
		$state_data = new Blackbox_StateData();

		// when we call isValid, the canRun() method will return false because
		// it cannot find the value we are looking for.
		$rule = $this->getMock('OLPBlackbox_Rule_NotCompare', array('onSkip', 'hitEvent', 'hitStat'));
		$rule->expects($this->once())->method('onSkip');
		$rule->setupRule(array(
					OLPBlackbox_Rule::PARAM_FIELD => 'NotAnArray',
					));

		$v = $rule->isValid($data, $state_data);
		$this->assertEquals(NULL, $v);
	}
	
	/**
	 * Test for a value that's not an array but the key its trying to use exists
	 * Expected Result: NULL
	 *
	 * @return void
	 */
	public function testNotArrayExists()
	{
		$data = new Blackbox_DataTestObj(array('NotAnArray'=>'OOPS'));
		$state_data = new Blackbox_StateData();

		// when we call isValid, the runRule() method will throw an exception
		// beause the data it got to compare was not an array.
		$rule = $this->getMock('OLPBlackbox_Rule_NotCompare', array('onError', 'hitEvent', 'hitStat'));
		$rule->expects($this->once())->method('onError');
		$rule->setupRule(array(
					OLPBlackbox_Rule::PARAM_FIELD => 'NotAnArray',
					));

		$v = $rule->isValid($data, $state_data);
		$this->assertEquals(NULL, $v);
	}
	
}

?>
