<?php 
	class ECash_Models_NadaBookFlag extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_book_flag_id', 'period', 'book_flag', 'book_name'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_book_flag_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_book_flag_id';
		}
		public function getTableName()
		{
			return 'nada_book_flag';
		}
	}
?>