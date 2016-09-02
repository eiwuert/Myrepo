<?php

class VendorAPI_Actions_HandleTriggersTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var VendorAPI_BasicDriver
	 */
	protected $driver;
	
	/**
	 * @var VendorAPI_Actions_HandleTriggers
	 */
	protected $action;
	
	public function setUp()
	{
		$this->markTestSkipped('This action is no longer used');
		$this->driver = $this->getMock(
			'VendorAPI_IDriver' 
		);
		
		$this->action = $this->getMock(
			'VendorAPI_Actions_HandleTriggers',
			array('hitStat'),
			array($this->driver)
		);
	}
	
	public function testActionHitsStatsForTriggers()
	{
		$this->action->expects($this->once())
			->method('hitStat')
			->with($this->equalTo('test_stat1'));
		
		$trigger = new VendorAPI_Blackbox_Trigger('test_action', 'test_stat1');
		$trigger->hit();
		
		$triggers = new VendorAPI_Blackbox_Triggers();
		$triggers->addTrigger($trigger);
		
		$state = new VendorAPI_StateObject();
		$state->triggers = $triggers;
		
		$actual_response = $this->action->execute(serialize($state));
		$this->checkResult($actual_response, VendorAPI_Response::SUCCESS, array('has_triggers' => TRUE));
	}
		
	public function testNoStatsHitForNoTriggers()
	{
		$this->action->expects($this->never())
			->method('hitStat')
			->withAnyParameters();
		
		$triggers = new VendorAPI_Blackbox_Triggers();
		
		$state = new VendorAPI_StateObject();
		$state->triggers = $triggers;
		
		$actual_response = $this->action->execute(serialize($state));
		$this->checkResult($actual_response, VendorAPI_Response::SUCCESS, array('has_triggers' => FALSE));
	}
	
	public function testNoStatsHitForUnsetTriggers()
	{
		$this->action->expects($this->never())
			->method('hitStat')
			->withAnyParameters();
		
		$state = new VendorAPI_StateObject();
		$actual_response = $this->action->execute(serialize($state));
		$this->checkResult($actual_response, VendorAPI_Response::SUCCESS, array('has_triggers' => FALSE));
	}
	
	public function testOneStatHitForMultipleTriggers()
	{
		$this->action->expects($this->once())
			->method('hitStat')
			->with($this->equalTo('test_stat1'));
		
		$trigger1 = new VendorAPI_Blackbox_Trigger('test_action', 'test_stat1');
		$trigger1->hit();
		
		$trigger2 = new VendorAPI_Blackbox_Trigger('test_action', 'test_stat2');
		$trigger2->hit();
		
		$triggers = new VendorAPI_Blackbox_Triggers();
		$triggers->addTrigger($trigger1);
		$triggers->addTrigger($trigger2);
		
		$state = new VendorAPI_StateObject();
		$state->triggers = $triggers;
		
		$actual_response = $this->action->execute(serialize($state));
		$this->checkResult($actual_response, VendorAPI_Response::SUCCESS, array('has_triggers' => TRUE));
	}
	
	public function testNoStatsHitForUnhitTriggers()
	{
		$this->action->expects($this->never())
			->method('hitStat');
		
		$trigger = new VendorAPI_Blackbox_Trigger('test_action', 'blah');
		$trigger->unhit();
				
		$triggers = new VendorAPI_Blackbox_Triggers();
		$triggers->addTrigger($trigger);
		
		$state = new VendorAPI_StateObject();
		$state->triggers = $triggers;
		
		$actual_response = $this->action->execute(serialize($state));
		$this->checkResult($actual_response, VendorAPI_Response::SUCCESS, array('has_triggers' => FALSE));
	}
	
	public function testNoStatsHitForEmptyStatTriggers()
	{
		$this->action->expects($this->never())
			->method('hitStat');
		
		$trigger = new VendorAPI_Blackbox_Trigger('test_action', '');
		$trigger->hit();
		
		$triggers = new VendorAPI_Blackbox_Triggers();
		$triggers->addTrigger($trigger);
		
		$state = new VendorAPI_StateObject();
		$state->triggers = $triggers;
		
		$actual_response = $this->action->execute(serialize($state));
		$this->checkResult($actual_response, VendorAPI_Response::SUCCESS, array('has_triggers' => TRUE));
	}
	
	public function testForExceptionOnNotPassingState()
	{
		$this->setExpectedException('InvalidArgumentException');
		
		$this->action->execute(serialize(new stdClass()));
	}
	
	public function testStatHidingBehindBlankStat()
	{
		$this->action->expects($this->once())
			->method('hitStat')
			->with($this->equalTo('test_stat2'));
		
		$trigger1 = new VendorAPI_Blackbox_Trigger('test_action', '');
		$trigger1->hit();
		
		$trigger2 = new VendorAPI_Blackbox_Trigger('test_action', 'test_stat2');
		$trigger2->hit();
		
		$triggers = new VendorAPI_Blackbox_Triggers();
		$triggers->addTrigger($trigger1);
		$triggers->addTrigger($trigger2);
		
		$state = new VendorAPI_StateObject();
		$state->triggers = $triggers;
		
		$actual_response = $this->action->execute(serialize($state));
		$this->checkResult($actual_response, VendorAPI_Response::SUCCESS, array('has_triggers' => TRUE));
	}
	
	public function testStatHit()
	{
		$statpro = $this->getMock('StatProClient', array('hitStat'));
		
		$this->driver->expects($this->any())
			->method('getStatProClient')
			->will($this->returnValue($statpro));
			
		$statpro->expects($this->once())
			->method('hitStat')
			->with($this->equalTo('test_stat1'), $this->equalTo('track1'), $this->equalTo('space1'));
		
		$trigger = new VendorAPI_Blackbox_Trigger('test_action', 'test_stat1');
		$trigger->hit();
		
		$triggers = new VendorAPI_Blackbox_Triggers();
		$triggers->addTrigger($trigger);
		
		$state = new VendorAPI_StateObject();
		$state->triggers = $triggers;
		$state->space_key = 'space1';
		$state->track_key = 'track1';
		
		$action = new VendorAPI_Actions_HandleTriggers($this->driver);
		$action->execute(serialize($state));
	}
	
	protected function checkResult(VendorAPI_Response $actual_response, $outcome, array $response_data)
	{
		$expected_response = new VendorAPI_Response(new VendorAPI_StateObject(), $outcome, $response_data);
		
		$this->assertEquals($expected_response->getOutcome(), $actual_response->getOutcome());
		$this->assertEquals($expected_response->getResult(), $actual_response->getResult());
	}
}

?>
