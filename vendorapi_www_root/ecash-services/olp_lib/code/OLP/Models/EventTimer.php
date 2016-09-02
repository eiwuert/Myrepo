<?php

/**
 * Database model for event_timer
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Models_EventTimer extends OLP_Models_WritableModel implements DB_Models_IReferenceable_1
{
	/**
	 * Attaches reference tables to this model and returns the new referenced model.
	 *
	 * @param DB_Models_ModelFactory_1 $factory
	 * @return DB_Models_ReferencedModel_1
	 */
	public function getReferencedModel(DB_Models_ModelFactory_1 $factory)
	{
		$events_table = $factory->getReferenceTable('Events');
		$environment_table = $factory->getReferenceTable('Environment');
		
		$reference_model = new DB_Models_Decorator_ReferencedWritableModel_1($this);
		$reference_model->addReferenceTable($events_table);
		$reference_model->addReferenceTable($environment_table);
		
		return $reference_model;
	}
	
	/**
	 * If we touch one of the timers, we need to mark them all as altered,
	 * to protect against any database inconsistencies.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function __set($name, $value)
	{
		parent::__set($name, $value);
		
		$keep_fresh = array(
			'date_started',
			'date_ended',
			'time_elapsed',
		);
		
		// Keep our volatile variables fresh!
		if (in_array($name, $keep_fresh))
		{
			foreach ($keep_fresh AS $variable_name)
			{
				$this->altered_columns[$variable_name] = $variable_name;
			}
		}
	}
	
	/**
	 * Process all the date columns.
	 *
	 * @return array
	 */
	public function getProcessedColumns()
	{
		$processed_columns = array(
			'date_started' => array(self::PROCESS_DATE),
			'date_ended' => array(self::PROCESS_DATE),
		);
		
		return $processed_columns;
	}
	
	/**
	 * List of columns for this model.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'event_timer_id',
			'application_id',
			'event_id',
			'environment_id',
			'date_started',
			'date_ended',
			'time_elapsed',
		);
		
		return $columns;
	}
	
	/**
	 * List of required columns for this model.
	 *
	 * @return array
	 */
	public function getRequiredColumns()
	{
		return array(
			'application_id',
			'event_id',
			'environment_id',
		);
	}
	
	/** List of primary keys for this model.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('event_timer_id');
	}
	
	/** The auto increment column for this model.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'event_timer_id';
	}
	
	/** The table name for this model.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'event_timer';
	}
}

?>
