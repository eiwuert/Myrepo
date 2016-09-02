<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test case for the OLPBlackbox_Rule class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_RuleTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that we hit the hitEvent and not hitBBStat when a rule passes.
	 *
	 * @return void
	 */
	public function testOnValidEventAndStatHits()
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();
		
		$rule = $this->getMock('OLPBlackbox_Rule',
			array('canRun', 'runRule', 'hitRuleEvent', 'hitRuleStat', 'hitEvent', 'hitBBStat')
		);
		$rule->expects($this->any())->method('canRun')->will($this->returnValue(TRUE));
		$rule->expects($this->any())->method('runRule')->will($this->returnValue(TRUE));
		
		$rule->expects($this->once())->method('hitRuleEvent')->with(OLPBlackbox_Config::EVENT_RESULT_PASS);
		$rule->expects($this->never())->method('hitRuleStat');
		
		$valid = $rule->isValid($data, $state_data);
		$this->assertTrue($valid);
	}
	
	/**
	 * Tests that we hit the hitEvent and hitBBStats functions when a rule fails.
	 *
	 * @return void
	 */
	public function testOnInvalidEventAndStatHits()
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();
		
		$rule = $this->getMock('OLPBlackbox_Rule',
			array('canRun', 'runRule', 'hitRuleEvent', 'hitRuleStat', 'hitEvent', 'hitBBStat')
		);
		$rule->expects($this->any())->method('canRun')->will($this->returnValue(TRUE));
		$rule->expects($this->any())->method('runRule')->will($this->returnValue(FALSE));
		
		$rule->expects($this->once())->method('hitRuleEvent')->with(OLPBlackbox_Config::EVENT_RESULT_FAIL);
		$rule->expects($this->once())->method('hitRuleStat')->with(OLPBlackbox_Config::STAT_RESULT_FAIL);
		
		$valid = $rule->isValid($data, $state_data);
		$this->assertFalse($valid);
	}
	
	/**
	 * Tests that we don't attempt to hitBBStat or hitEvent when they aren't defined.
	 * 
	 * @return void
	 */
	public function testUnpopulatedStatAndEvent()
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();
		
		$rule = $this->getMock('OLPBlackbox_Rule',
			array('canRun', 'runRule', 'hitEvent', 'hitBBStat'));
		$rule->expects($this->any())->method('canRun')->will($this->returnValue(TRUE));
		$rule->expects($this->any())->method('runRule')->will($this->returnValue(TRUE));
		
		$rule->expects($this->never())->method('hitEvent');
		$rule->expects($this->never())->method('hitBBStat');
		
		$valid = $rule->isValid($data, $state_data);
		$this->assertTrue($valid);
	}
	
	/**
	 * Tests that we do attempt to hitBBStat or hitEvent when they are defined.
	 * 
	 * @return void
	 */
	public function testPopulatedStatAndEvent()
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();
		
		$rule = $this->getMock('OLPBlackbox_Rule',
			array('canRun', 'runRule', 'hitEvent', 'hitBBStat'));
		$rule->expects($this->any())->method('canRun')->will($this->returnValue(TRUE));
		$rule->expects($this->any())->method('runRule')->will($this->returnValue(FALSE));
		
		$rule->expects($this->once())->method('hitEvent');
		$rule->expects($this->once())->method('hitBBStat');
		
		// setup stat and event names for the rule
		$rule->setEventName('TEST_EVENT');
		$rule->setStatName('test_stat');
		
		$valid = $rule->isValid($data, $state_data);
		$this->assertFalse($valid);
	}
}
?>