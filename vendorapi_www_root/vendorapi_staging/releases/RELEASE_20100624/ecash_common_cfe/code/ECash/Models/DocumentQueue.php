<?php
	/**
	 * @package Ecash.Models
	 */

	
	class ECash_Models_DocumentQueue extends ECash_Models_WritableModel  
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'company_id',
				'application_id', 'document_queue_id', 'document_name',
				'transaction_register_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('document_queue_id');
		}
		public function getAutoIncrement()
		{
			return 'document_queue_id';
		}
		public function getTableName()
		{
			return 'document_queue';
		}

		public function getColumnData()
		{
			$modified = $this->column_data;
			//mysql timestamps
			$modified['date_created'] = date("Y-m-d H:i:s", $modified['date_created']);
			return $modified;
		}
		
		public function setColumnData($column_data)
		{
			//mysql timestamps
			$column_data['date_created'] = strtotime($column_data['date_created']);
			$this->column_data = $column_data;
		}
		
	}
?>
