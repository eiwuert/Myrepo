<?php

/**
 * Test a OLP_ISubscriber object which sends a timeout event after receiving a
 * notify and having a certain amount of time elapsed.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLP
 * @subpackage EventBus
 */
class OLP_EventBus_Subscriber_TimeoutTest extends PHPUnit_Framework_TestCase
{
	const TIMEOUT_EVENT = 'TIMEOUT_EVENT';
	const ANY_EVENT = 'ANY_EVENT';
	
	/**
	 * Test the basic timeout functionality of this subscriber object.
	 *
	 * @dataProvider timeoutProvider
	 * @param float $timeout The length of time before the subscriber object
	 * "times out."
	 * @param float $wait_in_seconds How long to sleep in emulation of processing time.
	 * @param bool $expect_timeout Whether a timeout event should be sent.
	 * @return void
	 */
	public function testTimeout($timeout = .5, $wait_in_seconds = 1, $expect_timeout = TRUE)
	{
		$event_bus = new OLP_EventBus();
		$microsecond_wait = round($wait_in_seconds * 1000000);
		
		$subscriber = new OLP_Test_CollectingSubscriber();
		
		$event_bus->subscribeTo(self::TIMEOUT_EVENT, $subscriber);
		
		$timer = $this->freshSubscriberWithTimeout($timeout); 
		$timer->listenFor(array(self::ANY_EVENT))->listenTo($event_bus);
		
		
		// actual use case that we're testing
		$timer->start();
		usleep($microsecond_wait);
		$timer->notify($this->freshEvent(self::ANY_EVENT));
		
		
		// assertions
		$events = $subscriber->getEvents();
		
		$this->assertEquals(
			intval($expect_timeout), count($events), 
			"Subscriber did not receive 1 event as expected."
		);
		if ($expect_timeout)
		{
			$this->assertEquals(
				$events[0]->getType(), 
				self::TIMEOUT_EVENT,
				"Event collected by collecting subscriber was not a timeout event"
			);
		}
	}
	
	/**
	 * Data provider for {@see OLP_Subscriber_TimeoutTest::testTimeout()}
	 *
	 * @return array
	 */
	public static function timeoutProvider()
	{
		return array(
			// timeout of .5, wait .6 = should time out
			array(.5, 0.6, TRUE),
			// timeout of .5 but only wait .1, should not time out
			array(.5, 0.1, FALSE),
		);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * Return a new OLP_Subscriber_Timeout which will emit a timeout event.
	 *
	 * @param float $timeout The time in seconds to wait to send the timeout.
	 * @return OLP_Subscriber_Timeout
	 */
	protected function freshSubscriberWithTimeout($timeout)
	{
		return new OLP_Subscriber_Timeout(
			$this->freshEvent(self::TIMEOUT_EVENT), $timeout
		);
	}
	
	/**
	 * Returns a new mock OLP_IEvent object of type $event_type.
	 *
	 * @param string $event_type The event type this event should return when
	 * getType() is called.
	 * @return OLP_IEvent
	 */
	protected function freshEvent($event_type)
	{
		$event = $this->getMock('OLP_IEvent', array('getType'));
		$event->expects($this->any())->method('getType')->will($this->returnValue($event_type));
		return $event;
	}
}

?>