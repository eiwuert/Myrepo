<?php

	require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_RuleSet extends ECash_Models_WritableModel
	{
		public $LoanType;
		
		public static function getActiveByLoanType($loan_type_id,$override_dbs = NULL)
		{
			$query = "
					SELECT * 
					FROM 
						rule_set 
					WHERE 
						active_status = :active_status 
					AND 
						loan_type_id = :loan_type_id 
					AND
						date_effective = (
											SELECT
												MAX(date_effective)
											FROM
												rule_set
											WHERE
												active_status = :active_status
											AND
												loan_type_id = :loan_type_id
										)
					LIMIT 1
					";

			$where_args = array('active_status' => 'active',
								'loan_type_id'  => $loan_type_id);
								
			$base = new self();
			$base->setOverrideDatabases($override_dbs);
			if (($row = $base->getDatabaseInstance(self::DB_INST_READ)->querySingleRow($query, $where_args)) !== FALSE)
			{
				$base->fromDbRow($row);
				return $base;
			}
			return NULL;
		}
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status',
				'rule_set_id', 'name', 'loan_type_id', 'date_effective'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('rule_set_id');
		}
		public function getAutoIncrement()
		{
			return 'rule_set_id';
		}
		public function getTableName()
		{
			return 'rule_set';
		}
	}
?>