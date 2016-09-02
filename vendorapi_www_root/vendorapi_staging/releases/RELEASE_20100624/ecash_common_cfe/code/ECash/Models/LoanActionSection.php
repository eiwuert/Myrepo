<?php

	require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_LoanActionSection extends ECash_Models_WritableModel
	{
		public $LoanActionSection;
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'loan_action_section_id', 'name_short',
				'description'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('loan_action_section_id');
		}
		public function getAutoIncrement()
		{
			return 'loan_action_section_id';
		}
		public function getTableName()
		{
			return 'loan_action_section';
		}
	}
?>