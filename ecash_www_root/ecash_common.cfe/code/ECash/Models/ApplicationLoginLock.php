<?php

/**
 * application_login_lock
 *
 * @package Models
 * @author Russell Lee <russell.lee@sellingsource.com>
 */
class ECash_Models_ApplicationLoginLock extends ECash_Models_WritableModel
{
	/**
	 * The columns in the model
	 *
	 * @return array
	 */
	public function getColumns()
	{
		return array(
			'date_modified',
			'date_created',
			'application_login_lock_id',
			'application_id',
			'counter'
		);
	}

	/**
	 * The name of the model table
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'application_login_lock';
	}

	/**
	 * The primary key columns
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('application_login_lock_id');
	}

	/**
	 * The auto increment column
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'application_login_lock_id';
	}
}

?>
