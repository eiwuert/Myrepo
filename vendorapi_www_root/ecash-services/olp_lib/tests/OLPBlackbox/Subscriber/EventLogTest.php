<?php

/**
 * Tests an OLP_ISubscriber which writes to the event log.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 * @subpackage EventLog
 */
class OLPBlackbox_Subscriber_EventLogTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test basic functionality of logging received events to event log.
	 *
	 * @return void
	 */
	public function testLogCall()
	{
		$application_id = 111;
		$mode = 'RC';
		$event_type = 'ORIGINAL_EVENT_NAME';
		
		$event_log = $this->getMock('Event_Log', array('Log_Event'));
		$event_log->expects($this->exactly(2))
			->method('Log_Event')
			->with($event_type, OLPBlackbox_Subscriber_EventLog::EVENT_RESULT, NULL, $application_id, $mode);
		
		$event = $this->freshEventOfType($event_type);
		
		$subscriber = new OLPBlackbox_Subscriber_EventLog(
			$event_log,
			$application_id,
			$mode
		);
		
		$this->assertTrue(
			$subscriber instanceof OLP_ISubscriber, 
			get_class($subscriber) . ' is not an OLP_ISubscriber!'
		);
		
		$subscriber->notify($event);
		$subscriber->notify($event);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * Returns a mocked up OLP_IEvent which returns $type from getType();
	 *
	 * @param string $type
	 * @return OLP_IEvent
	 */
	protected function freshEventOfType($type)
	{
		$event = $this->getMock('OLP_IEvent', array('getType'));
		$event->expects($this->any())
			->method('getType')
			->will($this->returnValue($type));
		return $event;
	}
}

?>