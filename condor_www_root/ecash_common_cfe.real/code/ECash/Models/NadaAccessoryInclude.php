<?php 
	class ECash_Models_NadaAccessoryInclude extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_accessory_include_id', 'period', 'vic_make',
				'vic_year', 'option_table', 'vac', 'included_vac'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_accessory_include_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_accessory_include_id';
		}
		public function getTableName()
		{
			return 'nada_accessory_include';
		}
	}
?>