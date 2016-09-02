<?php


	class ECash_Models_LoanActionProcessList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_LoanActionProcess';
		}

		public function getTableName()
		{
			return 'loan_action_process';
		}
	}
?>
