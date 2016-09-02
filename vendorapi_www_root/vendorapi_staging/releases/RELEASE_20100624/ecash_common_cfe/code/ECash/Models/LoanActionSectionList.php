<?php
	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_LoanActionSectionList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_LoanActionSection';
		}

		public function getTableName()
		{
			return 'loan_action_section';
		}

	}
?>