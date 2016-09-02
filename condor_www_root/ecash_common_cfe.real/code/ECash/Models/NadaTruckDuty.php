<?php 
	class ECash_Models_NadaTruckDuty extends ECash_Models_WritableModel
	{
		public $Duty;
		public function getColumns()
		{
			static $columns = array(
				'nada_truck_duty_id', 'period', 'duty_id',
				'duty_description'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_truck_duty_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_truck_duty_id';
		}
		public function getTableName()
		{
			return 'nada_truck_duty';
		}
	}
?>