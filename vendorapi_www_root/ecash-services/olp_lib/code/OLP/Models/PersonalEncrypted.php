<?php

/*
 * This file was automatically generated by generate_writable_model.php
 * at 2009-10-09, 07:41:48 from 'mysql:host=reporting.dbproxy.tss;port=3314;dbname=olp'.
 *
 * NOTE: Modifications to this file will be overwritten if/when
 * it is regenerated.
 *
 */
class OLP_Models_PersonalEncrypted extends OLP_Models_CryptWritableModel  
{
	public function getProcessedColumns()
	{
		$processed_columns = array(
			'date_of_birth' => array(self::PROCESS_ENCRYPT),
			'social_security_number' => array(self::PROCESS_ENCRYPT),
			'drivers_license_num' => array(self::PROCESS_ENCRYPT)
		);
		
		// Merge in any processed columns from parent
		$parent_processed_columns = parent::getProcessedColumns();
		if (is_array($parent_processed_columns))
		{
			$processed_columns = array_merge_recursive($parent_processed_columns, $processed_columns);
		}
		
		return $processed_columns;
	}
	/**
	 * The list of columns this model contains
	 * @return array string[]
	 */
	public function getColumns()
	{
		static $columns = array(
			'application_id', 'modified_date', 'first_name',
			'middle_name', 'last_name', 'home_phone', 'cell_phone',
			'fax_phone', 'email', 'alt_email', 'date_of_birth', 'age',
			'contact_id_1', 'contact_id_2', 'social_security_number',
			'drivers_license_number', 'best_call_time',
			'drivers_license_state', 'email_agent_created', 'military',
			'name_prefix', 'drivers_license_num'
		);
		return $columns;
	}

	/**
	 * An array of the columns that comprise the primary key
	 * @return array string[]
	 */
	public function getPrimaryKey()
	{
		return array('application_id');
	}

	/**
	 * The auto increment column, if any
	 * @return string|void
	 */
	public function getAutoIncrement()
	{
		return NULL;
	}

	/**
	 * Indicates the table name
	 * @return string
	 */
	public function getTableName()
	{
		return 'personal_encrypted';
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
		$column_data['modified_date'] = date('Y-m-d H:i:s', $column_data['modified_date']);
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
		$column_data['modified_date'] = strtotime($column_data['modified_date']);
		parent::setColumnData($column_data);
	}
}