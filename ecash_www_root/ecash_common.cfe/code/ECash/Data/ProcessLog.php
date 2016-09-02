<?php

	class ECash_Data_ProcessLog extends ECash_Data_DataRetriever
	{
		/**
		 * @todo should switch to DateTime object and use format()
		 * @param timestamp $business_day optional, will be set to today
		 * @return string company short of currently running batch, or
		 * NULL if a batch is not currently running
		 */
		public function getStartedACHBatch($business_day = NULL)
		{
			if($business_day === NULL) $business_day = time();
			
			$query = "
				SELECT
					c.name_short
				FROM process_log log
				JOIN company c ON (c.company_id = log.company_id)
				WHERE log.business_day = ?
				AND log.step = 'ach_send'
				AND log.state = 'started'
			";
			return DB_Util_1::querySingleValue(
				$this->db,
				$query,
				array(date('Y-m-d', $business_day)));
		}
	}

?>