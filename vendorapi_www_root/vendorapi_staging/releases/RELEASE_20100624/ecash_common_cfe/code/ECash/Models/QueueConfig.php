<?php
	class ECash_Models_QueueConfig extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
				'queue_config_id', 'queue_id', 'config_key', 'config_value'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('queue_config_id');
		}
		public function getAutoIncrement()
		{
			return 'queue_config_id';
		}
		public function getTableName()
		{
			return 'n_queue_config';
		}
	}
?>