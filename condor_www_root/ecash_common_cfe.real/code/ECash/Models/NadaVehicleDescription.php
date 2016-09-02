<?php 
	class ECash_Models_NadaVehicleDescription extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_vehicle_description_id', 'period', 'vic_make',
				'vic_year', 'vic_series', 'vic_body', 'vid', 'make',
				'model', 'series', 'body', 'vehicle_segment', 'model_code',
				'msrp', 'weight', 'gvw', 'gcw', 'mileage_class',
				'truck_duty', 'option_table', 'shared_table', 'book_flag'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_vehicle_description_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_vehicle_description_id';
		}
		public function getTableName()
		{
			return 'nada_vehicle_description';
		}
	}
?>