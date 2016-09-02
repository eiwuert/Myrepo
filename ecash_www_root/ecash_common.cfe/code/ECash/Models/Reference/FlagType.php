<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Reference_FlagType extends DB_Models_ReferenceModel_1
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status', 'flag_type_id', 'name', 'name_short'
			);
			return $columns;
		}
		public function getColumnData()
		{
			$data = $this->column_data;
			$data['date_created'] = date("Y-m-d H:i:s", $data['date_created']);
			$data['date_modified'] = date("Y-m-d H:i:s", $data['date_modified']);
			return $data;
		}
		public function setColumnData($data)
		{
			$this->column_data = $data;
			$this->column_data['date_created'] = strtotime($data['date_created']);
			$this->column_data['date_modified'] = strtotime($data['date_modified']);
		}
		public function getPrimaryKey()
		{
			return array('flag_type_id');
		}
		public function getAutoIncrement()
		{
			return 'flag_type_id';
		}
		public function getTableName()
		{
			return 'flag_type';
		}

		public function getColumnID()
		{
			return 'flag_type_id';
		}

		public function getColumnName()
		{
			return 'name_short';
		}
	}
?>