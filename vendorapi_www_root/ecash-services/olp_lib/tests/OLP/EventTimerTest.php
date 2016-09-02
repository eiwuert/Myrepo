<?php

/**
 * Tests OLP_EventTimer
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_EventTimerTest extends PHPUnit_Framework_TestCase
{
	const ENVIRONMENT = 'phpunit';
	
	/**
	 * Data provider for testStartEvent().
	 *
	 * @return array
	 */
	public static function dataProviderStartEvent()
	{
		return array(
			array(
				'test_event',
				'test_environment',
				TRUE,
			),
			
			array(
				'',
				'test_environment',
				FALSE,
			),
			
			array(
				NULL,
				'test_environment',
				FALSE,
			),
			
			array(
				'test_event',
				'',
				FALSE,
			),
			
			array(
				'',
				'',
				FALSE,
			),
		);
	}
	
	/**
	 * Tests a simple run of startEvent().
	 *
	 * @dataProvider dataProviderStartEvent
	 *
	 * @return void
	 */
	public function testStartEvent($event, $environment, $expected_result)
	{
		$application_id = 1;
		
		$factory = $this->getMock(
			'OLP_Factory',
			array('getReferencedModel'),
			array(),
			'',
			FALSE
		);
		
		if ($expected_result)
		{
			// Using stdClass, because it is easier than mocking a ReferencedModel...
			$model = $this->getMock(
				'stdClass',
				array(
					'setInsertMode',
					'save',
				)
			);
			$model->expects($this->once())
				->method('save')
				->will($this->returnValue(TRUE));
		
			$factory->expects($this->once())
				->method('getReferencedModel')
				->with('EventTimer')
				->will($this->returnValue($model));
		}
		else
		{
			$factory->expects($this->never())
				->method('getReferencedModel');
		}
		
		$event_timer = new OLP_EventTimer($factory, $application_id);
		$this->assertEquals($expected_result, $event_timer->startEvent($event, $environment));
	}
	
	/**
	 * Tests a simple run of the event timer.
	 *
	 * @return void
	 */
	public function testEventTimer()
	{
		$application_id = 1;
		$event = __METHOD__;
		$environment = self::ENVIRONMENT;
		
		// Using stdClass, because it is easier than mocking a ReferencedModel...
		$model = $this->getMock(
			'stdClass',
			array(
				'setInsertMode',
				'save',
			)
		);
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
		$this->assertTrue($event_timer->startEvent($event, $environment));
		$this->assertTrue($event_timer->endEvent($event, $environment));
	}
	
	/**
	 * Data provider for testTimeElapsed().
	 *
	 * @return array()
	 */
	public static function dataProviderTimeElapsed()
	{
		return array(
			array(
				100000.0,
				100001.0,
				1.0,
			),
			
			array(
				500,
				501,
				1,
			),
			
			// Nothing to prevent negative time...
			array(
				87654321,
				12345678,
				-75308643,
			),
		);
	}
	
	/**
	 * Tests more complicated runs of the event timer system specifically
	 * for the time_elapsed variable.
	 *
	 * @dataProvider dataProviderTimeElapsed
	 *
	 * @param mixed $timestamp_start
	 * @param mixed $timestamp_end
	 * @param mixed $expected_time_elapsed
	 * @return void
	 */
	public function testTimeElapsed($timestamp_start, $timestamp_end, $expected_time_elapsed)
	{
		$application_id = 1;
		$event = __METHOD__;
		$environment = self::ENVIRONMENT;
		
		// Using stdClass, because it is easier than mocking a ReferencedModel...
		$model = $this->getMock(
			'stdClass',
			array(
				'setInsertMode',
				'save',
			)
		);
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
		$this->assertTrue($event_timer->startEvent($event, $environment, $timestamp_start));
		$this->assertTrue($event_timer->endEvent($event, $environment, $timestamp_end));
		$this->assertEquals($expected_time_elapsed, $model->time_elapsed);
	}
	
	/**
	 * Tests a run of loading from the database.
	 *
	 * @return void
	 */
	public function testLoadEventTimerFromDBPass()
	{
		$application_id = 1;
		$event = __METHOD__;
		$environment = self::ENVIRONMENT;
		$timestamp_start = 100000;
		$timestamp_end = 100001;
		$expected_time_elapsed = 1;
		
		// Using stdClass, because it is easier than mocking a ReferencedModel...
		$model = $this->getMock(
			'stdClass',
			array(
				'setInsertMode',
				'loadBy',
				'save',
			)
		);
		$model->application_id = $application_id;
		$model->event = strtolower($event);
		$model->environment = strtolower($environment);
		$model->date_started = $timestamp_start;
		$model->expects($this->once())
			->method('save')
			->will($this->returnValue(TRUE));
		$model->expects($this->once())
			->method('loadBy')
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
		$this->assertTrue($event_timer->endEvent($event, $environment, $timestamp_end));
		$this->assertEquals($expected_time_elapsed, $model->time_elapsed);
	}
	
	/**
	 * Tests a run of failing to load from the database.
	 *
	 * @return void
	 */
	public function testLoadEventTimerFromDBFail()
	{
		$application_id = 1;
		$event = __METHOD__;
		$environment = self::ENVIRONMENT;
		
		// Using stdClass, because it is easier than mocking a ReferencedModel...
		$model = $this->getMock(
			'stdClass',
			array(
				'setInsertMode',
				'loadBy',
				'save',
			)
		);
		$model->expects($this->once())
			->method('loadBy')
			->will($this->returnValue(FALSE));
		
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
		$this->assertFalse($event_timer->endEvent($event, $environment));
	}
}

?>
