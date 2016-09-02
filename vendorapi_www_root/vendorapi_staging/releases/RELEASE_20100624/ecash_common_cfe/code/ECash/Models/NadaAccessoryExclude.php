<?php 
	class ECash_Models_NadaAccessoryExclude extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_accessory_exclude_id', 'period', 'vic_make',
				'vic_year', 'option_table', 'vac', 'excluded_vac'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_accessory_exclude_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_accessory_exclude_id';
		}
		public function getTableName()
		{
			return 'nada_accessory_exclude';
		}
	}
?>