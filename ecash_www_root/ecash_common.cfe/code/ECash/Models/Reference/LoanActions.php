<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Reference_LoanActions extends ECash_Models_Reference_Model
	{               
		public function getColumns()
		{
			static $columns = array(
				'loan_action_id', 'name_short', 'description', 'status',
				'type'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('loan_action_id');
		}
		public function getAutoIncrement()
		{
			return 'loan_action_id';
		}
		public function getTableName()
		{
			return 'loan_actions';
		}
		
		public function getColumnID()
		{
			return 'loan_action_id';
		}

		public function getColumnName()
		{
			return 'name_short';
		}
	}
?>
