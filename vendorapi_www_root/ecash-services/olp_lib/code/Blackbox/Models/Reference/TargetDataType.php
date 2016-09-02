<?php
/**
 * TargetDataType reference model
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class Blackbox_Models_Reference_TargetDataType extends Blackbox_Models_Reference_Model
{
	/**
	 * Get columns for target_data_type
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'target_data_type_id', 'name'
		);
		return $columns;
	}

	/**
	 * Get the primary key for target_data_type
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('target_data_type_id');
	}

	/**
	 * Get auto-increment value for target_data_type
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'target_data_type_id';
	}

	/**
	 * Get the table name for target_data_type
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'target_data_type';
	}

	/**
	 * Get the column name containing the ID for target_data_type
	 *
	 * @return string
	 */
	public function getColumnID()
	{
		return 'target_data_type_id';
	}

	/**
	 * Get the column name containing the name for target_data_type
	 *
	 * @return string
	 */
	public function getColumnName()
	{
		return 'name';
	}
}
