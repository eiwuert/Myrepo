<?php

/**
 * Test main class of the EventBus subpackage.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLP
 * @subpackage EventBus
 */
class OLP_EventBusTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var string
	 */
	protected $event_type_one;
	
	/**
	 * @var string
	 */
	protected $event_type_two;
	
	/**
	 * @var string
	 */
	protected $event_type_two_lower;
	
	/**
	 * @var OLP_IEvent
	 */
	protected $event_one;
	
	/**
	 * @var OLP_IEvent
	 */
	protected $event_two;
	
	/**
	 * @var OLP_IEvent
	 */
	protected $event_two_lower;
	
	/**
	 * @var OLP_IEventBus
	 */
	protected $bus;
	
	/**
	 * Create common, fresh fixtures for each test method.
	 * @return void
	 */
	public function setUp()
	{
		$this->event_type_one = 'EVENT_ONE';
		$this->event_type_two = 'EVENT_TWO';
		$this->event_type_two_lower = strtolower($this->event_type_two);
		
		$this->event_one = $this->getEvent($this->event_type_one);
		$this->event_two = $this->getEvent($this->event_type_two);
		$this->event_two_lower = $this->getEvent($this->event_type_two_lower);
		
		$this->bus = new OLP_EventBus();
	}
	
	/**
	 * Test basic subscribeTo, unsubscribeFrom methods.
	 *
	 * @return void
	 */
	public function testBasicPublishAndSubscribe()
	{
		$subscriber = $this->freshSubscriberExpectingEvents($this->event_type_one, 1);
		
		$this->bus->subscribeTo($this->event_type_one, $subscriber);
		
		$this->bus->notify($this->event_one);
		
		$this->bus->unsubscribeFrom($this->event_type_one, $subscriber);
		$this->bus->notify($this->event_one);
	}
	
	/**
	 * Make sure that even if a subscriber gets subscribed to the same event more
	 * than once, only one event is received.
	 *
	 * @return void
	 */
	public function testDoubleSubscribe()
	{
		$subscriber = $this->freshSubscriberExpectingEvents($this->event_type_one, 1);
		
		$this->bus->subscribeTo($this->event_type_one, $subscriber);
		$this->bus->subscribeTo($this->event_type_one, $subscriber);
		
		$this->bus->notify($this->event_one);
	}
	
	/**
	 * Tests that no connections are left behind when an event is unsubscribed
	 * from the bus and then resubscribes to an event.
	 * 
	 * ... this may seem odd, but some implementations of the bus are
	 * vulnerable to this.
	 *
	 * @return void
	 */
	public function testSubscriptionsSeveredProperly()
	{
		$subscriber = $this->freshSubscriberExpectingEvents($this->event_type_two, 1);
		
		$this->bus->subscribeTo($this->event_type_one, $subscriber);
		$this->bus->unsubscribe($subscriber);
		$this->bus->subscribeTo($this->event_type_two, $subscriber);
		
		
		// the subscription to event_type_one should have been "erased"
		$this->bus->notify($this->getEvent($this->event_type_one));
		$this->bus->notify($this->getEvent($this->event_type_two));
	}
	
	/**
	 * Test normalizer.
	 *
	 * @return void
	 */
	public function testNormalizer()
	{
		$subscriber = $this->freshSubscriberExpectingEvents($this->event_type_two_lower, 1);
		
		$this->bus->subscribeTo($this->event_type_two, $subscriber);
		
		$this->bus->notify($this->event_two_lower);
		
		$this->bus->unsubscribeFrom($this->event_type_two, $subscriber);
		$this->bus->notify($this->event_two_lower);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * Returns a mocked OLP_IEvent object of type $event_type.
	 *
	 * @param string $event_type The event type to mock the event with. (any
	 * string, really)
	 * @return OLP_IEvent
	 */
	protected function getEvent($event_type)
	{
		$event = $this->getMock('OLP_IEvent', array('getType'));
		$event->expects($this->any())
			->method('getType')
			->will($this->returnValue($event_type));
		
		return $event;
	}

	/**
	 * Get an OLP_ISubscriber which will expect an event type a particular number
	 * of times.
	 *
	 * @param string $event_type The event type to expect.
	 * @param int $expected_call_count The amount of times the subscriber should
	 * return an event of $event_type type.
	 * @return OLP_ISubscriber
	 */
	protected function freshSubscriberExpectingEvents($event_type, $expected_call_count = 1)
	{
		$method_equals_constraint = new Framework_Constraint_Method(
			$this->equalTo($event_type), 'getType'
		);
		
		$listener = $this->getMock('OLP_ISubscriber');
		$listener->expects($this->exactly($expected_call_count))
			->method('notify')
			->with($method_equals_constraint);
		
		return $listener;
	}
}

?>
