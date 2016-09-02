<?php 
	class ECash_Models_NadaVehicleValue extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_vehicle_value_id', 'period', 'vic_make', 'vic_year',
				'vic_series', 'vic_body', 'region', 'value_type', 'value'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_vehicle_value_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_vehicle_value_id';
		}
		public function getTableName()
		{
			return 'nada_vehicle_value';
		}
	}
?>