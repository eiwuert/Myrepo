<?php 
	class ECash_Models_NadaAccessoryVin extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_accessory_vin_id', 'period', 'vin_prefix',
				'vin_sequence', 'vin_vac'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_accessory_vin_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_accessory_vin_id';
		}
		public function getTableName()
		{
			return 'nada_accessory_vin';
		}
	}
?>