<?php
/**
 * Rule Collection Class model.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Models_Reference_RuleCollectionClass extends Blackbox_Models_Reference_Model
{
	public function getColumns()
		{
			static $columns = array(
				'rule_collection_class_id', 'class'
			);
			return $columns;
		}
	
	public function getPrimaryKey()
	{
		return array('rule_collection_class_id');
	}
	
	public function getAutoIncrement()
	{
		return 'rule_collection_class_id';
	}
	
	public function getTableName()
	{
		return 'rule_collection_class';
	}
	
	public function getColumnID()
	{
		return 'rule_collection_class_id';
	}

	public function getColumnName()
	{
		return 'class';
	}
}
