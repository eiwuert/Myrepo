<?php

	class ECash_Data_ACH extends ECash_Data_DataRetriever
	{
		public function hasFatalFailures($application_id)
		{
			$query = "
				SELECT count(*) as 'count'
				FROM ach
				JOIN ach_return_code ON (ach_return_code.ach_return_code_id = ach.ach_return_code_id)
				WHERE
					application_id = ?
					and ach_return_code.is_fatal = 'yes'";

			return (DB_Util_1::querySingleValue($this->db, $query, array($application_id)) > 0);
		}

		/**
		 * @todo This could be a reference list possibly, but what it's doing
		 * is so psychotic and im so close to losing my mind that ill have
		 * to come back to it.
		 *
		 */
		public function getAchEventTypes($company_id = NULL)
		{
			if ($company_id !== NULL)
			{
				$company_limit = "AND tt.company_id = {$company_id}";
			}
			else
			{
				$company_limit = "";
			}

			$query = "
				SELECT DISTINCT
					et.event_type_id
				FROM
					event_transaction et
				join transaction_type tt on (et.transaction_type_id	= tt.transaction_type_id)
				WHERE					
					tt.clearing_type		= 'ach'
					{$company_limit}
			";

			return DB_Util_1::querySingleColumn($this->db, $query);
		}
	}

?>
