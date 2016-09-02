<?php

/**
 * The model for section.
 *
 * @package Model
 * @author Auto Generated
 */
class ECash_Models_Section extends DB_Models_WritableModel_1
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
			'date_modified',
			'date_created',
			'active_status',
			'system_id',
			'section_id',
			'name',
			'description',
			'section_parent_id',
			'sequence_no',
			'level',
			'read_only_option',
			'can_have_queues'
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
		return 'section';
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
			'section_id'
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
		return 'section_id';
	}
}
?>