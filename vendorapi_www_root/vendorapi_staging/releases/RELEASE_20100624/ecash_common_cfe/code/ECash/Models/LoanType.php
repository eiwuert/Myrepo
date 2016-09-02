<?php

	require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_LoanType extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public $Company;
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status',
				'company_id', 'loan_type_id', 'name', 'name_short', 'abbreviation'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('loan_type_id');
		}
		public function getAutoIncrement()
		{
			return 'loan_type_id';
		}
		public function getTableName()
		{
			return 'loan_type';
		}
	}
?>
