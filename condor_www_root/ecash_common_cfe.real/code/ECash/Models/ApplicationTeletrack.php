<?php 
	/**
	 * Data Model for the application_teletrack table
	 * 
	 * @desc This particular table is used to store and retrieve the TransactionCode
	 * that gets assigned to each customer during a Fund Update.
	 * 
	 * @author Brian Ronald <brian.ronald@sellingsource.com>
	 *
	 */
	class ECash_Models_ApplicationTeletrack extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'application_id', 'transaction_code', 'date_created', 'date_modified'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('application_id');
		}
		public function getAutoIncrement()
		{
			return 'application_id';
		}
		public function getTableName()
		{
			return 'application_teletrack';
		}
		public function getColumnData()
		{
			$column_data = parent::getColumnData();
			$column_data['date_created'] = date('Y-m-d H:i:s', $column_data['date_created']);
			return $column_data;
		}		
		public function setColumnData($data)
		{
			$this->column_data = $data;
			$this->column_data['date_created'] = strtotime($data['date_created']);
		}
	}
