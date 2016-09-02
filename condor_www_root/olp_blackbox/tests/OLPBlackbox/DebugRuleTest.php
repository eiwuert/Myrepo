<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * OLPBlackbox_DebugRule test case.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class DebugRuleTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Is valid should always return TRUE.
	 *
	 * @return void
	 */
	public function testIsValid()
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();
		
		$rule = $this->getMock(
			'OLPBlackbox_DebugRule',
			array('hitRuleEvent', 'hitStat', 'hitEvent'),
			array('test')
		);
		
		// We won't hit stats on skipped rules
		$rule->expects($this->never())->method('hitStat');
		$rule->expects($this->once())->method('hitRuleEvent')->with(OLPBlackbox_Config::EVENT_RESULT_DEBUG_SKIP);

		$this->assertTrue($rule->isValid($data, $state_data));
	}
}

