<?php 
	class ECash_Models_NadaVehicleVin extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_vehicle_vin_id', 'period', 'vin_prefix',
				'vin_sequence', 'vic_make', 'vic_year', 'vic_series',
				'vic_body', 'gvw', 'ton_rating_low', 'ton_rating_high',
				'book_flag'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_vehicle_vin_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_vehicle_vin_id';
		}
		public function getTableName()
		{
			return 'nada_vehicle_vin';
		}
	}
?>