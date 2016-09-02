<?php 
	class ECash_Models_NadaAccessoryValue extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_accessory_value_id', 'period', 'vic_make', 'vic_year',
				'option_table', 'vac', 'region', 'value_type', 'value'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_accessory_value_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_accessory_value_id';
		}
		public function getTableName()
		{
			return 'nada_accessory_value';
		}
	}
?>