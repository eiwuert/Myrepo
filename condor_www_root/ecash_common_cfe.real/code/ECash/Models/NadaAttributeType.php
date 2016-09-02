<?php 
	class ECash_Models_NadaAttributeType extends ECash_Models_WritableModel
	{
		public $Attribute;
		public function getColumns()
		{
			static $columns = array(
				'nada_attribute_type_id', 'period', 'attribute_id',
				'attribute_description'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_attribute_type_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_attribute_type_id';
		}
		public function getTableName()
		{
			return 'nada_attribute_type';
		}
	}
?>