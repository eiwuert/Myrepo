<?php
	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_LoanActionSectionRelationList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_LoanActionSectionRelationList';
		}

		public function createInstance(array $db_row)
		{
			$item = new ECash_Models_LoanActionSectionRelation($this->getDatabaseInstance());
			$item->fromDbRow($db_row);
			return $item;
		}
		
		public function loadBy(array $where_args)
		{
			$query = "SELECT * FROM loan_action_section_relation " . self::buildWhere($where_args);
			$this->statement = DB_Util_1::queryPrepared(
					$this->getDatabaseInstance(),
					$query,
					$where_args
			);
		}
	}
?>