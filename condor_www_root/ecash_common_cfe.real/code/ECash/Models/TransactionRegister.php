<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_TransactionRegister extends ECash_Models_WritableModel
	{
		const STATUS_NEW = 'new';
		const STATUS_PENDING = 'pending';
		const STATUS_COMPLETE = 'complete';
		const STATUS_FAILED = 'failed';

		public function getColumns()
		{
			static $columns = array(
				'date_modified','date_created','company_id','application_id','transaction_register_id','event_schedule_id','ach_id','ecld_id','transaction_type_id','transaction_status','amount','date_effective','source_id','modifying_agent_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('transaction_register_id');
		}
		public function getAutoIncrement()
		{
			return 'transaction_register_id';
		}
		public function getTableName()
		{
			return 'transaction_register';
		}

		/** all below are @depricated, please insure they exist in ECash_Data_Transaction and delete from here */
		public static function getFailedCount($application_id, array $override_dbs = NULL)
		{
			$query = "SELECT count(*)
						FROM transaction_register
						WHERE application_id = ?
						AND transaction_status = '".self::STATUS_FAILED."'";
			
			$base = new self();
			$base->setOverrideDatabases($override_dbs);

			$st = $base->getDatabaseInstance(self::DB_INST_READ)->prepare($query);
			$st->execute(array($application_id));

			return $st->fetchColumn();
		}

		public static function hasPendingQuickChecks($application_id, array $override_dbs = NULL)
		{
			$query = "
				SELECT count(*)
				FROM transaction_register
				WHERE transaction_status = '".self::STATUS_PENDING."'
				AND application_id = ?
				AND transaction_type_id in (SELECT transaction_type_id
                            FROM transaction_type
                            WHERE name_short = '".ECash_Models_TransactionType::CLEARING_QUICKCHECK."')";

			$base = new self();
			$base->setOverrideDatabases($override_dbs);

			$st = $base->getDatabaseInstance(self::DB_INST_READ)->prepare($query);
			$st->execute(array($application_id));

			return (($st->fetchColumn() > 0) ? TRUE : FALSE);
		}

		public static function getBalance($application_id, array $override_dbs = NULL)
		{
			$query = "
				SELECT sum(amount)
				FROM transaction_register
				WHERE transaction_status = '".self::STATUS_COMPLETE."'
				AND application_id = ?";

			$base = new self();
			$base->setOverrideDatabases($override_dbs);

			$st = $base->getDatabaseInstance(self::DB_INST_READ)->prepare($query);
			$st->execute(array($application_id));

			return $st->fetchColumn();
		}

		public static function hasCompletedQuickChecks($application_id, array $override_dbs = NULL)
		{
			$query = "
				SELECT count(*)
				FROM transaction_register
				WHERE transaction_status = '".self::STATUS_COMPLETE."'
				AND application_id = ?
				AND transaction_type_id in (SELECT transaction_type_id
                		FROM transaction_type
		                WHERE name_short = '".ECash_Models_TransactionType::CLEARING_QUICKCHECK."')";

			$base = new self();
			$base->setOverrideDatabases($override_dbs);

			$st = $base->getDatabaseInstance(self::DB_INST_READ)->prepare($query);
			$st->execute(array($application_id));

			return $st->fetchColumn();
		}

	}
?>