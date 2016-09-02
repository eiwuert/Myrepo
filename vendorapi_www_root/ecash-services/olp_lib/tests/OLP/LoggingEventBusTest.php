<?php

/**
 * Test the logging version of the bus.
 *
 * @package OLP
 * @subpackage EventBus
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLP_LoggingEventBusTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test that the events we expect to be logged are, and contain at least 
	 * SOME content that indicates what we'd like to see in the log without
	 * imposing a lot of specifics on the output.
	 * @return void
	 */
	public function testNotifyLog()
	{
		$event_type_one = 'EVENT_ONE';
		$event_type_two = 'EVENT_TWO';
		$event_type_three = 'EVENT_THREE';
		
		$subscriber_a_name = ':Subscriber A:';
		$subscriber_b_name = ':Subscriber B:';
		
		$logger = new Log_DebugILog();
		
		$subscriber_a = $this->getMockSubscriber($subscriber_a_name);
		$subscriber_b = $this->getMockSubscriber($subscriber_b_name);
		
		$bus = new OLP_LoggingEventBus($logger);
		$bus->subscribeTo($event_type_one, $subscriber_a);
		
		$bus->subscribeTo($event_type_two, $subscriber_a);
		$bus->subscribeTo($event_type_two, $subscriber_b);
		
		$bus->subscribeTo($event_type_three, $subscriber_b);
		$bus->unsubscribeFrom($event_type_three, $subscriber_b);
		
		$bus->notify(new OLPBlackbox_Event($event_type_one));
		$bus->notify(new OLPBlackbox_Event($event_type_two));
		$bus->notify(new OLPBlackbox_Event($event_type_three));
		
		$this->assertTrue(TRUE);
		$expected_results = array(
			// A subscribes to ONE
			"/$subscriber_a_name/i",
			// A and B subscribe to two
			"/$event_type_two/i",
			"/$event_type_two/i",
			// B subscribes to three... then unsubscribes
			"/$event_type_three/i",
			"/$subscriber_b_name/i",
			// Event one is sent, heard by A
			"/$event_type_one/i",
			"/$subscriber_a_name/i",
			// Event two is sent, heard by A and B
			"/$event_type_two/i",
			"/$subscriber_a_name/i",
			"/$subscriber_b_name/i",
			// Event three is sent, hear by no one
			"/$event_type_three/i",
		);
		
		$item = 0;
		foreach ($expected_results as $key => $pattern)
		{
			++$item;
			$this->assertTrue((bool)preg_match($pattern, $logger->logs[$key]),
				"Log message didn't contain content that was expected ($pattern) at entry {$item}"
			);
		}
	}
		
	/**
	 * Returns a mocked Subscriber object with notify() mocked.
	 *
	 * @param string $to_string The string this object should return for the 
	 * __toString() method.
	 * @return OLP_ISubscriber
	 */
	protected function getMockSubscriber($to_string)
	{
		$subscriber = $this->getMock('OLP_ISubscriber', array('notify', '__toString'));
		$subscriber->expects($this->any())
			->method('__toString')
			->will($this->returnValue($to_string));
		return $subscriber;
	}
}

?>