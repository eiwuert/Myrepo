<?php
	class ECash_Models_LoanActionHistoryList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_LoanActionHistory';
		}

		public function getTableName()
		{
			return 'loan_action_history';
		}
	}
?>