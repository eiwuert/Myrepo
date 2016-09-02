<?php 
	class ECash_Models_NadaAccessoryBodyInclude extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_accessory_body_include_id', 'period', 'vic_make',
				'vic_year', 'vic_series', 'vic_body', 'included_vac'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_accessory_body_include_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_accessory_body_include_id';
		}
		public function getTableName()
		{
			return 'nada_accessory_body_include';
		}
	}
?>