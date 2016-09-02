<?php 
	class ECash_Models_NadaAccessoryCategory extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public $Category;
		public function getColumns()
		{
			static $columns = array(
				'nada_accessory_category_id', 'period', 'category_id',
				'category_description'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_accessory_category_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_accessory_category_id';
		}
		public function getTableName()
		{
			return 'nada_accessory_category';
		}
	}
?>