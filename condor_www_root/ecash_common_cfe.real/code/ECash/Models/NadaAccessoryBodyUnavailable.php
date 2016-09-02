<?php 
	class ECash_Models_NadaAccessoryBodyUnavailable extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_accessory_body_unavailable_id', 'period', 'vic_make',
				'vic_year', 'vic_series', 'vic_body', 'unavailable_vac'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_accessory_body_unavailable_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_accessory_body_unavailable_id';
		}
		public function getTableName()
		{
			return 'nada_accessory_body_unavailable';
		}
	}
?>