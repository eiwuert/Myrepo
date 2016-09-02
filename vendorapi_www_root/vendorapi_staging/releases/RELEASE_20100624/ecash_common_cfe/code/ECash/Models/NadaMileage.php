<?php 
	class ECash_Models_NadaMileage extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_mileage_id', 'period', 'vic_year', 'mileage_class',
				'range_high', 'range_low', 'percent_flag', 'amount',
				'ctg_range_high', 'ctg_range_low', 'ctg_high_adjust',
				'ctg_low_adjust'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_mileage_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_mileage_id';
		}
		public function getTableName()
		{
			return 'nada_mileage';
		}
	}
?>