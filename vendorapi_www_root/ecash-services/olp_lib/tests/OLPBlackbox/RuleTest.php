<?php
/**
 * Test case for the OLPBlackbox_Rule class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_RuleTest extends PHPUnit_Framework_TestCase
{

	/**
	 * Data provider for the function below
	 *
	 * @return array
	 */
	public function dataProviderEventAndStatHits()
	{
		return array(
			array('d1', TRUE),
			array('d1', FALSE),
			array('cac', TRUE),
			array('cac', FALSE)
		);
	}

	/**
	 * Tests that we hit the hitEvent and not hitBBStat when a rule passes for Enterprise campaigns.
	 *
	 * @dataProvider dataProviderEventAndStatHits
	 * @param string $campaign_name
	 * @param bool $show_eventlog_passes
	 * @return void
	 */
	public function testOnValidEventAndStatHitsForEnterprise($campaign_name, $show_eventlog_passes)
	{
		$data = new Blackbox_Data();
		$state_data = new OLPBlackbox_CampaignStateData(array(
			'campaign_name' => $campaign_name,
			'eventlog_show_rule_passes' => $show_eventlog_passes
		));
		
		$rule = $this->getMock('OLPBlackbox_Rule',
			array('canRun', 'runRule', 'hitRuleEvent', 'hitRuleStat', 'hitEvent', 'hitBBStat')
		);
		$rule->expects($this->any())->method('canRun')->will($this->returnValue(TRUE));
		$rule->expects($this->any())->method('runRule')->will($this->returnValue(TRUE));
		
		if ($show_eventlog_passes)
		{
			$rule->expects($this->once())->method('hitRuleEvent')->with(OLPBlackbox_Config::EVENT_RESULT_PASS);
		}
		else
		{
			$rule->expects($this->never())->method('hitRuleEvent');
		}
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

	/**
	 * Data provider for testExceptionPropagation
	 * array((string)ExceptionName, (bool)propagates)
	 *
	 * @return array
	 */
	public function reworkPropagationProvider()
	{
		return array(
			array('OLPBlackbox_ReworkException', TRUE),
			array('OLPBlackbox_FailException', TRUE),
			array('Blackbox_Exception', FALSE),
			array('Exception', FALSE),
		);
	}
	
	/**
	 * Tests that when runRule throws a propagating exception that the rule does not
	 * prevent that exception from propagating 
	 *
	 * @return void
	 * @dataProvider reworkPropagationProvider
	 */
	public function testExceptionPropagation($exception, $propagates)
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock(
			'OLPBlackbox_Rule',
			array('canRun', 'runRule', 'hitEvent', 'hitBBStat')
		);
		$rule->expects($this->any())
			->method('canRun')
			->will($this->returnValue(TRUE));
		$rule->expects($this->any())
			->method('runRule')
			->will($this->throwException(new $exception($exception . ' being thrown')));
			
		// If an exception is expected to propagate, let the test case know its name
		if ($propagates)
		{
			$this->setExpectedException($exception);
		}

		$result = $rule->isValid($data, $state_data);
		
		// If an exception should not propagate, it should return False
		if (!$propagates)
		{
			$this->assertFalse($result);
		}
		
	}

	/**
	 * Data provider for testTestAppLogsEventOnValid
	 *
	 * @return array
	 */
	public function providerTestAppLogsEventOnValid()
	{
		return array(
			array(TRUE, 1),
			array(FALSE, 0),
		);
	}
	
	/**
	 * Test that hitRuleEvent will log passes based on TEST_APP flag being set
	 *
	 * @param bool $flag_exists Return for OLP_ApplicationFlag::flagExists
	 * @param int $hit_event_calls Number of times hitRuleEvent will be called
	 * @dataProvider providerTestAppLogsEventOnValid
	 */
	public function testTestAppLogsEventOnValid($flag_exists, $hit_event_calls)
	{
		$data = new Blackbox_Data();
		$state = new Blackbox_StateData();
		
		$app_flags = $this->getMock("OLP_ApplicationFlag", array("flagExists"), array(), "", FALSE);
		$app_flags->expects($this->any())
			->method("flagExists")
			->will($this->returnValue($flag_exists));

		$config = new OLPBlackbox_Config();//OLPBlackbox_Config::getInstance();
		$config->app_flags = $app_flags;
		
		$rule = $this->getMock(
			'OLPBlackbox_Rule',
			array('canRun', 'runRule', 'hitRuleEvent', 'getConfig')
		);
		$rule->expects($this->any())
			->method("canRun")
			->will($this->returnValue(TRUE));
		$rule->expects($this->any())
			->method("runRule")
			->will($this->returnValue(TRUE));
		$rule->expects($this->exactly($hit_event_calls))
			->method("hitRuleEvent");
		$rule->expects($this->any())
			->method("getConfig")
			->will($this->returnValue($config));
			
		
		$rule->isValid($data, $state);
	}
	
	/**
	 * @expectedException RuntimeException
	 */
	public function testSetSourceWithStateGivesException()
	{
		$rule = new TestRuleWithPublicGetData();
		$rule->setDataSource(OLPBlackbox_Config::DATA_SOURCE_STATE);
	}
	
	public function testGetDataFromBlackboxData() 
	{
		$data = new OLPBlackbox_Data();
		$data->application_id = 22;
				
		$rule = new TestRuleWithPublicGetData();
		$rule->setupRule(array(Blackbox_StandardRule::PARAM_FIELD => 'application_id'));
		$this->assertEquals(22, $rule->getDataValue($data));
	}
	
	public function testGetDataFromConfig()
	{
		$data = new OLPBlackbox_Data();
		$config = new OLPBlackbox_Config();
		$config->somevalue = "hello world";
		
		$rule = new TestRuleWithPublicGetData();
		$rule->setConfig($config);
		$rule->setupRule(array(Blackbox_StandardRule::PARAM_FIELD => 'somevalue'));
		$rule->setDataSource(OLPBlackbox_Config::DATA_SOURCE_CONFIG);
		
		$this->assertEquals("hello world", $rule->getDataValue($data));
	}
}

/**
 * Publishes a few of the rule methods so that I can test
 * them easier.
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class TestRuleWithPublicGetData extends OLPBlackbox_Rule
{
	protected $config;
	
	public function setConfig(OLPBlackbox_Config $config)
	{
		$this->config = $config;
	}
	
	public function getConfig()
	{
		return $this->config;
	}
	
	public function getDataValue(BlackBox_Data $data)
	{
		return parent::getDataValue($data);
	}
	
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;	
	}
}
?>
