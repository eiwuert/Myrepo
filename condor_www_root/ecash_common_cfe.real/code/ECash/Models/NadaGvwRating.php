<?php 
	class ECash_Models_NadaGvwRating extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_gvw_rating_id', 'period', 'gvw_code', 'gvw_low',
				'gvw_high'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_gvw_rating_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_gvw_rating_id';
		}
		public function getTableName()
		{
			return 'nada_gvw_rating';
		}
	}
?>