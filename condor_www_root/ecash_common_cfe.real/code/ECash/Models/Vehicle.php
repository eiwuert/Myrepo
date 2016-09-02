<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_Vehicle extends ECash_Models_WritableModel
	{


		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created','vehicle_id', 'application_id', 'vin', 'license_plate','make','model',
				'series','style','color','year','mileage','value','title_state','modifying_agent_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('vehicle_id');
		}
		public function getAutoIncrement()
		{
			return 'vehicle_id';
		}
		public function getTableName()
		{
			return 'vehicle';
		}
	}
?>