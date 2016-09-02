<?php

	require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_LoanActionProcess extends ECash_Models_WritableModel
	{
		public $LoanAction;
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'loan_action_section_relation_id', 
				'application_status_id', 'current_application_status_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('loan_action_section_relation_id');
		}
		public function getAutoIncrement()
		{
			return 'loan_action_section_relation_id';
		}
		public function getTableName()
		{
			return 'loan_action_process';
		}
	}
?>
