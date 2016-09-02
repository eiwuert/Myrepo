<?php 
	class ECash_Models_NadaTonCode extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_ton_code_id', 'period', 'ton_code', 'ton_rating'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_ton_code_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_ton_code_id';
		}
		public function getTableName()
		{
			return 'nada_ton_code';
		}
	}
?>