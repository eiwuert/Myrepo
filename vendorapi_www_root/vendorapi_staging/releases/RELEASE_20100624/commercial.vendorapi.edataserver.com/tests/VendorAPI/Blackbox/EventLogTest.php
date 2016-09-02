<?php

class VendorAPI_Blackbox_EventLogTest extends PHPUnit_Framework_TestCase
{
	private $state;

	/**
	 * @var VendorAPI_Blackbox_EventLog
	 */
	private $log;

	public function setUp()
	{
		$this->state = new VendorAPI_StateObject();
		$this->log = new VendorAPI_Blackbox_EventLog(
			$this->state,
			1,
			'ca'
		);
	}

	public function tearDown()
	{
		$this->state = null;
		$this->log = null;
	}

	public function testEventsBelowLevelAreNotAddedToState()
	{
		$this->log->setLevel(VendorAPI_Blackbox_EventLog::FAIL);
		$this->log->logEvent('test', 'woot', VendorAPI_Blackbox_EventLog::DEBUG);

		$this->assertArrayNotHasKey('eventlog', $this->state->getTableDataSince());
	}

	public function testEventsAboveLevelAreAddedToState()
	{
		$this->log->setLevel(VendorAPI_Blackbox_EventLog::PASS);
		$this->log->logEvent('test', 'woot', VendorAPI_Blackbox_EventLog::FAIL);

		$expected = array(
			'application_id' => 1,
			'target' => 'ca',
			'event' => 'test',
			'response' => 'woot',
		);

		$state = $this->state->getTableDataSince();

		$actual = $state['eventlog'][0];
		unset($actual['date_created']);

		$this->assertEquals($expected, $actual);
	}

	public function testEventsEqualToLevelAreAddedToState()
	{
		$this->log->setLevel(VendorAPI_Blackbox_EventLog::PASS);
		$this->log->logEvent('test', 'woot', VendorAPI_Blackbox_EventLog::PASS);

		$expected = array(
			'application_id' => 1,
			'target' => 'ca',
			'event' => 'test',
			'response' => 'woot',
		);

		$state = $this->state->getTableDataSince();

		$actual = $state['eventlog'][0];
		unset($actual['date_created']);

		$this->assertEquals($expected, $actual);
	}

	public function testLevelCanBeSetInConstructor()
	{
		$log = new VendorAPI_Blackbox_EventLog($this->state, 1, 'ca', VEndorAPI_Blackbox_EventLog::FAIL);
		$this->log->logEvent('test', 'woot', VendorAPI_Blackbox_EventLog::PASS);

		$this->assertArrayNotHasKey('eventlog', $this->state->getTableDataSince());
	}
}

?>