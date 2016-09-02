<?php
/**
 * BlackboxType model
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Models_Reference_BlackboxType extends Blackbox_Models_Reference_Model
{
	public function getColumns()
		{
			static $columns = array(
				'blackbox_type_id', 'name'
			);
			return $columns;
		}
	
	public function getPrimaryKey()
	{
		return array('blackbox_type_id');
	}
	
	public function getAutoIncrement()
	{
		return 'blackbox_type_id';
	}
	
	public function getTableName()
	{
		return 'blackbox_type';
	}
	
	public function getColumnID()
	{
		return 'blackbox_type_id';
	}

	public function getColumnName()
	{
		return 'name';
	}
}
