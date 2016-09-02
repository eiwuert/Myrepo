<?php 
	class ECash_Models_CardStatus extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'card_status_id', 'name',
				'name_short', 'is_fundable', 'is_enabled', 'is_valid'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('card_status_id');
		}
		public function getAutoIncrement()
		{
			return 'card_status_id';
		}
		public function getTableName()
		{
			return 'card_status';
		}
		public function getDatabaseInstance()
		{
			throw new Exception("Method not implemented.");
		}
	}
 ?>