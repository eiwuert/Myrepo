<?php

class Event1Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Event_1
	 */
	protected $event;

	public function setUp()
	{
		$this->event = new Event_1();
	}

	public function testParamsArePrepended()
	{
		$expected = array('d', 'a', 'b', 'c');

		$d = $this->getMock('Delegate_1', array('invokeArray'), array(), '', FALSE);
		$d->expects($this->exactly(2))
			->method('invokeArray')
			->with($expected);

		$this->event->addDelegate($d);
		$this->event->setParams(array('d'));

		// test both here, since we'd have two of the EXACT same methods :(
		$this->event->invokeArray(array('a', 'b', 'c'));
		$this->event->invoke('a', 'b', 'c');
	}
}

?>