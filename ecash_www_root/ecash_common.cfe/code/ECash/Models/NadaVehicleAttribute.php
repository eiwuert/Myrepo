<?php 
	class ECash_Models_NadaVehicleAttribute extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public $Attribute;
		public function getColumns()
		{
			static $columns = array(
				'nada_vehicle_attribute_id', 'period', 'vic_make',
				'vic_year', 'vic_series', 'vic_body', 'attribute_id',
				'attribute_value'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_vehicle_attribute_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_vehicle_attribute_id';
		}
		public function getTableName()
		{
			return 'nada_vehicle_attribute';
		}
	}
?>