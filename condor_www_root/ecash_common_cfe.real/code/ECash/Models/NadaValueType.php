<?php 
	class ECash_Models_NadaValueType extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_value_type_id', 'period', 'value_type', 'book_flag',
				'value_name'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_value_type_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_value_type_id';
		}
		public function getTableName()
		{
			return 'nada_value_type';
		}
	}
?>