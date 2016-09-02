<?php

	/**
         * @package Ecash.Models
         */

	class ECash_Models_Tokens extends ECash_Models_ObservableWritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'token_id', 'company_id', 'loan_type_id', 'token_name',
				'value_array'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('token_id');
		}
		public function getAutoIncrement()
		{
			return 'token_id';
		}
		public function getTableName()
		{
			return 'tokens';
		}
	}
?>
