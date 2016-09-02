<?php

/**
 * Rule Trigger model
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Blackbox_Models_Reference_RuleTrigger extends Blackbox_Models_Reference_Model
{
	public function getColumns()
	{
		static $columns = array(
			'rule_trigger_id',
			'name',
		);
		
		return $columns;
	}
	
	public function getPrimaryKey()
	{
		return array('rule_trigger_id');
	}
	
	public function getAutoIncrement()
	{
		return 'rule_trigger_id';
	}
	
	public function getTableName()
	{
		return 'rule_trigger';
	}
	
	public function getColumnID()
	{
		return 'rule_trigger_id';
	}

	public function getColumnName()
	{
		return 'name';
	}
}

?>