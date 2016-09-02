<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_AgentAffiliationReasonList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_AgentAffiliationReason';
		}
		public function createInstance(array $db_row, array $override_dbs = NULL)
		{
			$model = new ECash_Models_AgentAffiliationReason($this->getDatabaseInstance());
			$model->fromDbRow($db_row);

			return $model;
		}
		public function loadBy(array $where_args)
		{
			$query = "SELECT * FROM agent_affiliation_reason " . self::buildWhere($where_args);
			$this->statement = DB_Util_1::queryPrepared(
					$this->getDatabaseInstance(),
					$query,
					$where_args
			);
		}


	}

?>
