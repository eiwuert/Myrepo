<?php 
	class ECash_Models_NadaVehicleSegment extends ECash_Models_WritableModel
	{
		public $Segment;
		public function getColumns()
		{
			static $columns = array(
				'nada_vehicle_segment_id', 'period', 'segment_id',
				'segment_description'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_vehicle_segment_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_vehicle_segment_id';
		}
		public function getTableName()
		{
			return 'nada_vehicle_segment';
		}
	}
?>