<?php

/**
 * Rule Event Type model
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Blackbox_Models_Reference_RuleEventType extends Blackbox_Models_Reference_Model
{
	public function getColumns()
	{
		static $columns = array(
			'rule_event_type_id',
			'name',
		);
		
		return $columns;
	}
	
	public function getPrimaryKey()
	{
		return array('rule_event_type_id');
	}
	
	public function getAutoIncrement()
	{
		return 'rule_event_type_id';
	}
	
	public function getTableName()
	{
		return 'rule_event_type';
	}
	
	public function getColumnID()
	{
		return 'rule_event_type_id';
	}

	public function getColumnName()
	{
		return 'name';
	}
}

?>