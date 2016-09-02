<?php

/**
 * Tests OLP_EventTimer
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_EventTimerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests a simple run of the event timer.
	 *
	 * @return void
	 */
	public function testBasic()
	{
		$application_id = 1;
		$event = __METHOD__;
		
		// Using stdClass, because it is easier than mocking a ReferencedModel...
		$model = $this->getMock(
			'stdClass',
			array(
				'loadBy',
				'isStored',
				'save'
			)
		);
		$model->date_ended = NULL; // So we don't get any notices
		$model->expects($this->once())
			->method('loadBy')
			->will($this->returnValue(FALSE));
		$model->expects($this->once())
			->method('isStored')
			->will($this->returnValue(FALSE));
		$model->expects($this->exactly(2))
			->method('save')
			->will($this->returnValue(TRUE));
		
		$factory = $this->getMock(
			'OLP_Factory',
			array('getReferencedModel'),
			array(),
			'',
			FALSE
		);
		$factory->expects($this->once())
			->method('getReferencedModel')
			->with('EventTimer')
			->will($this->returnValue($model));
		
		$event_timer = new OLP_EventTimer($factory, $application_id);
		$this->assertTrue($event_timer->startEvent($event));
		$this->assertTrue($event_timer->endEvent($event));
	}
}

?>
