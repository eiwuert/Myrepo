<?php
/**
 * Target collection class reference model.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Models_Reference_TargetCollectionClass extends Blackbox_Models_Reference_Model
{
	/**
	 * Returns the columns of the table.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		return array(
			'target_collection_class_id', 'class'
		);
	}
	
	/**
	 * Returns the primary key for the table.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('target_collection_class_id');
	}
	
	/**
	 * Returns the auto increment column for the table.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'target_collection_class_id';
	}
	
	/**
	 * Returns the table name.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'target_collection_class';
	}
	
	/**
	 * Returns the ID column for the reference table.
	 *
	 * @return string
	 */
	public function getColumnID()
	{
		return 'target_collection_class_id';
	}

	/**
	 * Returns the name column for the reference table.
	 *
	 * @return string
	 */
	public function getColumnName()
	{
		return 'class';
	}
}

