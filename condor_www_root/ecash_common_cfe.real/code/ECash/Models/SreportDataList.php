<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_SreportDataList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_SreportDataList';
		}

		public function createInstance(array $db_row)
		{
			$item = new ECash_Models_SreportData($this->getDatabaseInstance());
			$item->fromDbRow($db_row);
			return $item;
		}
		
		public function loadBy(array $where_args, array $override_dbs = NULL)
		{
			$query = "SELECT * FROM sreport_data ". self::buildWhere($where_args);
			$db = $this->getDatabaseInstance();
			$this->statement = $db->queryPrepared($query, $where_args);
		}
	}
?>
