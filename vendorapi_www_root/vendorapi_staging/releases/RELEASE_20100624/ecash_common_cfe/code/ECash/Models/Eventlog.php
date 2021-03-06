<?php

/*
 * This file was automatically generated by generate_writable_model.php
 * at 2009-02-05, 15:19:21 from 'mysql:host=db117.ept.tss;port=3306;dbname=ldb_occ'.
 *
 * NOTE: Modifications to this file will be overwritten if/when
 * it is regenerated.
 *
 */
class ECash_Models_Eventlog extends ECash_Models_WritableModel
{
	/**
	 * The list of columns this model contains
	 * @return array string[]
	 */
	public function getColumns()
	{
		static $columns = array(
			'eventlog_id', 'application_id', 'date_created',
			'eventlog_event_id', 'eventlog_response_id',
			'eventlog_target_id'
		);
		return $columns;
	}

	/**
	 * An array of the columns that comprise the primary key
	 * @return array string[]
	 */
	public function getPrimaryKey()
	{
		return array('eventlog_id');
	}

	/**
	 * The auto increment column, if any
	 * @return string|void
	 */
	public function getAutoIncrement()
	{
		return 'eventlog_id';
	}

	/**
	 * Indicates the table name
	 * @return string
	 */
	public function getTableName()
	{
		return 'eventlog';
	}

	/**
	 * Gets the column data for updating/insertion
	 *
	 * This is used to perform per-column transformations as the data goes into the database.
	 *
	 * @return array
	 */
	public function getColumnData()
	{
		$column_data = parent::getColumnData();
		$column_data['date_created'] = date('Y-m-d H:i:s', $column_data['date_created']);
		return $column_data;
	}

	/**
	 * Sets the column data in the model
	 *
	 * This is used to perform per-column transformation as the data comes from the database
	 *
	 * @return void
	 */
	protected function setColumnData($column_data)
	{
		$column_data['date_created'] = strtotime($column_data['date_created']);
		parent::setColumnData($column_data);
	}
}

 ?>