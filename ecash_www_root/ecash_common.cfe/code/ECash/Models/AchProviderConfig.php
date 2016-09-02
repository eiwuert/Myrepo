<?php
	class ECash_Models_AchProviderConfig extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
				'ach_provider_config_id', 'ach_provider_id', 'config_key', 'config_value'
			);
			return $columns;
		}

		public function getPrimaryKey()
		{
			return array('ach_provider_config_id');
		}

		public function getAutoIncrement()
		{
			return 'ach_provider_config_id';
		}

		public function getTableName()
		{
			return 'ach_provider_config';
		}
	}
?>