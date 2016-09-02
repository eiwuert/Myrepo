<?php 
	class ECash_Models_NadaState extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'nada_state_id', 'period', 'state_name', 'state_code',
				'region_code'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('nada_state_id');
		}
		public function getAutoIncrement()
		{
			return 'nada_state_id';
		}
		public function getTableName()
		{
			return 'nada_state';
		}
	}
?>