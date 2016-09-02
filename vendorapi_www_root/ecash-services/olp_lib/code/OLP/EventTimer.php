<?php

/**
 * The OLP Database implementation of IEventTimer.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_EventTimer implements OLP_IEventTimer
{
	/**
	 * @var OLP_Factory
	 */
	protected $factory;
	
	/**
	 * Contains a subarray of environments, which contains
	 * 'model' (OLP_Model_EventTimer) and 'start_time' (float).
	 *
	 * @var array
	 */
	protected $events = array();
	
	/**
	 * Create a new instance of EventTimer for this application.
	 *
	 * @param OLP_Factory $factory
	 * @param int $application_id
	 */
	public function __construct(OLP_Factory $factory, $application_id)
	{
		$this->factory = $factory;
		$this->application_id = $application_id;
	}
	
	/**
	 * Starts a timer for an event. If one already is started, overwrites it.
	 *
	 * @param string $event
	 * @param string $environment
	 * @param int $timestamp
	 * @return bool
	 */
	public function startEvent($event, $environment, $timestamp = NULL)
	{
		$valid = FALSE;
		$event = $this->normalizeVariable($event);
		$environment = $this->normalizeVariable($environment);
		
		if ($timestamp === NULL)
		{
			$timestamp = microtime(TRUE);
			$start_time = $timestamp;
		}
		elseif (is_float($timestamp))
		{
			$start_time = $timestamp;
		}
		else
		{
			$start_time = NULL;
		}
		
		if (!empty($event) && !empty($environment))
		{
			$model = $this->getModel();
			$model->application_id = $this->application_id;
			$model->event = $event;
			$model->environment = $environment;
			$model->date_started = $timestamp;
			$model->date_ended = NULL;
			$model->time_elapsed = NULL;
			$valid = $model->save();
			
			$this->setEventTimer($model, $start_time);
		}
		
		return $valid;
	}
	
	/**
	 * Ends a timer for an event. If we do not have the event locally,
	 * will attempt to end one already started in the database.
	 *
	 * @param string $event
	 * @param string $environment
	 * @param int $timestamp
	 * @return bool
	 */
	public function endEvent($event, $environment, $timestamp = NULL)
	{
		$valid = FALSE;
		$event = $this->normalizeVariable($event);
		$environment = $this->normalizeVariable($environment);
		
		if ($timestamp === NULL)
		{
			$timestamp = microtime(TRUE);
			$end_time = $timestamp;
		}
		elseif (is_float($timestamp))
		{
			$end_time = $timestamp;
		}
		else
		{
			$end_time = NULL;
		}
		
		$event_timer = $this->getEventTimer($event, $environment);
		if ($event_timer)
		{
			$model = $event_timer['model'];
			$start_time = $event_timer['start_time'];
			
			// If either start or end timers are null, use model/timestamp
			if ($start_time && $end_time)
			{
				$model->time_elapsed = $end_time - $start_time;
			}
			else
			{
				$model->time_elapsed = $timestamp - $model->date_started;
			}
			
			$model->date_ended = $timestamp;
			$valid = $model->save();
			
			$this->setEventTimer($model, $start_time, $end_time);
		}
		
		return $valid;
	}
	
	/**
	 * Returns information about an event.
	 *
	 * @param string $event
	 * @param string $environment
	 * @return array
	 */
	public function getEventInformation($event, $environment)
	{
		$information = NULL;
		$event = $this->normalizeVariable($event);
		$environment = $this->normalizeVariable($environment);
		
		$event_timer = $this->getEventTimer($event, $environment);
		if ($event_timer)
		{
			$information = array(
				'event' => $event,
				'environment' => $environment,
				'start_time' => isset($event_timer['start_time']) ? $event_timer['start_time'] : $event_timer['model']->date_started,
				'end_time' => isset($event_timer['end_time']) ? $event_timer['end_time'] : $event_timer['model']->date_ended,
				'time_elapsed' => $event_timer['model']->time_elapsed,
			);
		}
		
		return $information;
	}
	
	/**
	 * Gets an event/environment model.
	 *
	 * @param string $event
	 * @param string $environment
	 * @param bool $load_from_db
	 * @return array
	 */
	protected function getEventTimer($event, $environment, $load_from_db = TRUE)
	{
		$timer = NULL;
		
		if (isset($this->events[$event][$environment]))
		{
			$timer = $this->events[$event][$environment];
		}
		
		if (!$timer)
		{
			// If we don't have a local copy, load from database
			$model = $this->loadEventTimerFromDB($event, $environment);
			
			if ($model)
			{
				$this->setEventTimer($model);
				
				$timer = $this->getEventTimer($event, $environment, FALSE);
			}
		}
		
		return $timer;
	}
	
	/**
	 * Loads an event/environment model from database.
	 *
	 * @param string $event
	 * @param string $environment
	 * @return OLP_Model_EventTimer
	 */
	protected function loadEventTimerFromDB($event, $environment)
	{
		$model = $this->getModel($event);
		
		$load_by = array(
			'application_id' => $this->application_id,
			'event' => $event,
			'environment' => $environment,
		);
		if (!$model->loadBy($load_by))
		{
			$model = NULL;
		}
		
		return $model;
	}
	
	/**
	 * Saves an event/environment model.
	 *
	 * @param DB_Models_WritableModel_1 $model
	 * @param int $start_time
	 * @param int $end_time
	 * @return void
	 */
	protected function setEventTimer($model, $start_time = NULL, $end_time = NULL)
	{
		$event = $model->event;
		$environment = $model->environment;
		
		if (!isset($this->events[$event]))
		{
			$this->events[$event] = array();
		}
		
		$this->events[$event][$environment] = array(
			'model' => $model,
			'start_time' => $start_time,
			'end_time' => $end_time,
		);
	}
	
	/**
	 * Gets a new model.
	 *
	 * @return OLP_Model_EventTimer
	 */
	protected function getModel()
	{
		$model = $this->factory->getReferencedModel('EventTimer');
		$model->setInsertMode(DB_Models_WritableModel_1::INSERT_ON_DUPLICATE_KEY_UPDATE);
		
		return $model;
	}
	
	/**
	 * Normalizes events/environments.
	 *
	 * @param string $variable
	 * @return string
	 */
	protected function normalizeVariable($variable)
	{
		return strtolower($variable);
	}
}

?>
