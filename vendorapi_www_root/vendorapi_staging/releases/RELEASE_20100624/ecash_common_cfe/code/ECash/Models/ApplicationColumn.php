<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_ApplicationColumn extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'company_id', 'application_id', 'table_name',
				'column_name', 'bad_info', 'do_not_contact', 'best_contact', 'do_not_market',
				'do_not_loan',
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('application_id', 'table_name', 'column_name');
		}
		public function getAutoIncrement()
		{
			return NULL;
		}
		public function getTableName()
		{
			return 'application_column';
		}
	}
?>
