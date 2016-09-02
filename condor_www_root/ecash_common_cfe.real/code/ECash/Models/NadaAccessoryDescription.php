<?php 
	class ECash_Models_NadaAccessoryDescription extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_accessory_description_id', 'period', 'vac',
				'accessory_description', 'accessory_category'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_accessory_description_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_accessory_description_id';
		}
		public function getTableName()
		{
			return 'nada_accessory_description';
		}
	}
?>