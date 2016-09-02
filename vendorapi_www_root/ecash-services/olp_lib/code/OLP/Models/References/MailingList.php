<?php

/**
 * Database model for mailing_list
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLP_Models_References_MailingList extends OLP_Models_References_ReferenceModel
{
	/**
	 * List of columns for the table.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'mailing_list_id',
			'name',
		);
		
		return $columns;
	}
	
	/**
	 * Primary key(s) for the table
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('mailing_list_id');
	}
	
	/**
	 * Get the auto increment column for the table
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'mailing_list_id';
	}
	
	/**
	 * Get the table name
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'mailing_list';
	}
	
	/**
	 * Get reference column ID
	 *
	 * @return string
	 */
	public function getColumnID()
	{
		return 'mailing_list_id';
	}
	
	/**
	 * Get the value for the reference table
	 *
	 * @return string
	 */
	public function getColumnName()
	{
		return 'name';
	}
}

?>
