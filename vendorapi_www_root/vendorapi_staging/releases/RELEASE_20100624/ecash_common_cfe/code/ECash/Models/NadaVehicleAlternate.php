<?php 
	class ECash_Models_NadaVehicleAlternate extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_vehicle_alternate_id', 'period', 'vic_make',
				'vic_year', 'vic_series', 'vic_body', 'alt_make',
				'alt_year', 'alt_series', 'alt_body'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_vehicle_alternate_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_vehicle_alternate_id';
		}
		public function getTableName()
		{
			return 'nada_vehicle_alternate';
		}
	}
?>