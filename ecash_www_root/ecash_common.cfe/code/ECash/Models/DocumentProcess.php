<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_DocumentProcess extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'document_list_id',
				'application_status_id', 'current_application_status_id'
			);
			return $columns;
		}

		
		public function getPrimaryKey()
		{
			return array('document_list_id', 'application_status_id', 'current_application_status_id');
		}
		public function getAutoIncrement()
		{
			return null;
		}
		public function getTableName()
		{
			return 'document_process';
		}

		public function getColumnData()
		{
			$column_data = $this->column_data;
			$column_data['date_created'] = date("Y-m-d H:i:s", $this->column_data['date_created']);
			$column_data['date_modified'] = date("Y-m-d H:i:s", $this->column_data['date_modified']);
			return $column_data;
		}

		public function setColumnData($data)
		{
			$this->column_data = $data;
			$this->column_data['date_created'] = strtotime($data['date_created']);
			$this->column_data['date_modified'] = strtotime($data['date_modified']);
		}
	}
?>