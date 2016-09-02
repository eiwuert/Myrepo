<?php

/**
 * The model for email_response.
 *
 * @package Model
 * @author Auto Generated
 */
class ECash_Models_EmailResponse extends DB_Models_WritableModel_1
	implements
		ECash_Models_IHasPermanentData
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
			'email_response_id',
			'response_name',
			'response_text',
			'company_id',
			'active_status',
			'date_modified',
			'date_created'
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
		return 'email_response';
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
			'email_response_id'
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
		return 'email_response_id';
	}
}
?>