<?php

/**
 * The model for bureau.
 *
 * @package Model
 * @author Auto Generated
 */
class ECash_Models_Reference_Bureau extends ECash_Models_Reference_Model
	implements
		DB_Models_IReferenceModel_1
{
	/**
	 * Override this method with one that returns an array of valid
	 * column names.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		return array(
			'date_created',
			'date_modified',
			'active_status',
			'bureau_id',
			'name',
			'name_short',
			'url_live',
			'port_live',
			'url_test',
			'port_test'
		);
	}

	/**
	 * Override this method with one that returns a string containing
	 * the name of your table.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'bureau';
	}

	/**
	 * Override this method to return an array containing the primary key
	 * column(s) for your table.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array(
			'bureau_id'
		);
	}

	/**
	 * Override this method with one that returns the name of the auto_increment
	 * column in your table. Return NULL if your table does not contain an
	 * auto_increment column.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'bureau_id';
	}


	/**
	 * Returns the column that contains the table ID of each item
	 * @return string
	 */
	public function getColumnID()
	{
		return 'bureau_id';
	}

	/**
	 * Returns the column that contains the name of each item
	 * @return string
	 */
	public function getColumnName()
	{
		return 'name_short';
	}
}
?>