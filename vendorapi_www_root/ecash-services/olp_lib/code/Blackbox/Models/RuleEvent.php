<?php

/**
 * Rule event database model
 *
 * @author Ryan Murphy <ryan.murphy@sellinsource.com>
 */
class Blackbox_Models_RuleEvent extends Blackbox_Models_WriteableModel
{
	/**
	 * Returns the column names for the model
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'rule_event_id',
			'rule_id',
			'rule_trigger_id',
			'rule_event_type_id',
		);
		
		return $columns;
	}
	
	/**
	 * Returns the name of the table
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'rule_event';
	}
}

?>