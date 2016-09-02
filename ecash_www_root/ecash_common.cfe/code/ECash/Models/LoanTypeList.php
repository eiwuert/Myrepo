<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_LoanTypeList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_LoanType';
		}

		public function getTableName()
		{
			return 'loan_type';
		}

		public function loadBusinessLoanTypes()
		{
			$this->statement = DB_Util_1::queryPrepared($this->getDatabaseInstance(),
														"SELECT * FROM loan_type lt JOIN cfe_rule_set crs ON (crs.loan_type_id = lt.loan_type_id)",
														array());
		}
	}
?>
