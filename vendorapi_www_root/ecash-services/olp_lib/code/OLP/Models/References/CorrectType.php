<?php

/**
 * Database model for correct_type
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Models_References_CorrectType extends OLP_Models_References_ReferenceModel
{
	/**
	 * List of columns for this model.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'correct_type_id',
			'date_created',
			'type',
		);
		
		return $columns;
	}
	
	/**
	 * List of primary keys for this model.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('correct_type_id');
	}
	
	/**
	 * The auto increment column for this model.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'correct_type_id';
	}
	
	/**
	 * The table name for this model.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'correct_type';
	}
	
	/**
	 * For the reference model, this is the ID column.
	 *
	 * @return string
	 */
	public function getColumnID()
	{
		return 'correct_type_id';
	}
	
	/**
	 * For the reference model, this is the VALUE column.
	 *
	 * @return string
	 */
	public function getColumnName()
	{
		return 'type';
	}
}

?>
