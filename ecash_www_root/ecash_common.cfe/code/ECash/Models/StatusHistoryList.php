<?php

	class ECash_Models_StatusHistoryList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_StatusHistory';
		}

		public function getTableName()
		{
			return 'status_history';
		}
	}

?>