<?php

/**
 * Target and property short reference class.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Blackbox_Models_Reference_TargetPropertyShort extends Blackbox_Models_Reference_Model
{
	/**
	 * Returns the columns of the table.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		return array(
			'target_id',
			'property_short',
			'blackbox_type_id',
		);
	}
	
	/**
	 * Returns the primary key for the table.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('target_id');
	}
	
	/**
	 * Returns the auto increment column for the table.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'target_id';
	}
	
	/**
	 * Returns the table name.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'target';
	}
	
	/**
	 * Returns the ID column for the reference table.
	 *
	 * @return string
	 */
	public function getColumnID()
	{
		return 'target_id';
	}

	/**
	 * Returns the name column for the reference table.
	 *
	 * @return string
	 */
	public function getColumnName()
	{
		return 'property_short';
	}
}

