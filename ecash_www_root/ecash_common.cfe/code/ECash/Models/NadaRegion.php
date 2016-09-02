<?php 
	class ECash_Models_NadaRegion extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_region_id', 'period', 'region_code', 'region_name'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_region_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_region_id';
		}
		public function getTableName()
		{
			return 'nada_region';
		}
	}
?>