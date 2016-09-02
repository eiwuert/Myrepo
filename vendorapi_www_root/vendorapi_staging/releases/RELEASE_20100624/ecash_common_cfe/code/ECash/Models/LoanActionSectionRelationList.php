<?php
	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_LoanActionSectionRelationList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_LoanActionSectionRelation';
		}

		public function getTableName()
		{
			return 'loan_action_section_relation';
		}
	}
?>
