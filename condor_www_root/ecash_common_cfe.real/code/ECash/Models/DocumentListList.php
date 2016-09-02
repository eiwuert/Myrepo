<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_DocumentListList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_DocumentListList';
		}

		public function createInstance(array $db_row)
		{
			$item = new ECash_Models_Reference_DocumentList($this->getDatabaseInstance());
			$item->fromDbRow($db_row);
			return $item;
		}
		public function loadBy(array $where_args)
		{
			$query = "SELECT * FROM document_list " . self::buildWhere($where_args);
			$this->statement = DB_Util_1::queryPrepared(
					$this->getDatabaseInstance(),
					$query,
					$where_args
			);
		}
		public function loadReferenceData()
		{
			$this->loadBy(array('active_status' => 'active', 'only_receivable' => 'no'));
		}
	}
?>			
