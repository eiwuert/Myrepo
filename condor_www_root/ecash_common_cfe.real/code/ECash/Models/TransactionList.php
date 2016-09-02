<?php

	/**
	 * @package Ecash.Models
	 *
	 * Encompasses transaction & transaction_ledger
	 */

	class ECash_Models_TransactionList extends ECash_Models_IterativeModel
	{
		const INDEX_TRANSACTION = 'transaction';
		const INDEX_LEDGER = 'transaction_ledger';
		const COLUMN_SEPARATOR = '_';

		private $transaction;
		private $ledger;

	   	public function __construct(DB_IConnection_1 $db)
		{
			parent::__construct($db);

			// these are used for reference data only (column names, table names)
			$this->transaction = ECash::getFactory()->getModel('Transaction');
			$this->ledger = ECash::getFactory()->getModel('TransactionLedger');
		}

		public function getClassName()
		{
			return NULL;
		}

        public function createInstance(array $db_row)
        {
			//create three different models and return them
			$ledger = NULL;

			$transaction = ECash::getFactory()->getModel('Transaction');
            $transaction->fromDbRow($db_row, $transaction->getTableName() . self::COLUMN_SEPARATOR);

			if($db_row[$this->ledger->getTableName() . self::COLUMN_SEPARATOR . $this->ledger->getAutoIncrement()] !== NULL)
			{
				$ledger = ECash::getFactory()->getModel('TransactionLedger');
	            $ledger->fromDbRow($db_row, $ledger->getTableName() . self::COLUMN_SEPARATOR);
			}

			return array(
				self::INDEX_TRANSACTION => $transaction,
				self::INDEX_LEDGER => $ledger
				);
        }

		public function loadBy(array $where_args)
		{
			/**
			 * This is kind of a goofy implementation, but I'd rather have it then
			 * sets of identical code following each other [JustinF]
			 */
			foreach($where_args as $column => $value)
			{
				if(!$this->fixWhere($where_args, $column, $this->transaction))
				{
					$this->fixWhere($where_args, $column, $this->ledger);
				}
			}

			/** @TODO this query could use getTableName() etc. from the models */

			$query = '
				SELECT
					' . $this->buildSelectColumns($this->transaction) . ',
					' . $this->buildSelectColumns($this->ledger) . '
				FROM transaction
				left JOIN transaction_ledger on (transaction_ledger.transaction_id = transaction.transaction_id)
				' . self::buildWhere($where_args, FALSE) . ' ORDER BY transaction.transaction_id';

			$this->statement = DB_Util_1::queryPrepared(
					$this->getDatabaseInstance(),
					$query,
					array_values($where_args)
			);
		}

		/**
		 * Builds a string of 'table_name.column_name as
		 * table_name_column_name, <again>' for pulling in multiple
		 * 1:1 models at once
		 *
		 * @param DB_Models_WritableModel_1 $model model to build a select segment from
		 */
		private function buildSelectColumns(DB_Models_WritableModel_1 $model)
		{
			$table_name = $model->getTableName();
			$column_names = $model->getColumns();
			$column_select = array();
			foreach($column_names as $column_name)
			{
				$column_select[] = $table_name . '.' . $column_name . ' as ' . $table_name . self::COLUMN_SEPARATOR . $column_name;
			}
			return join(",\n", $column_select);
		}

		/**
		 * Adds table name prefix to column in the where statement to
		 * avoid name collisions and complaints by MySQL
		 *
		 * @param array &$where array of where arguements to be manipulated
		 * @param string $column column name to be examined
		 * @param DB_Models_WritableModel_1 $model model to use for column and table names
		 */
		private function fixWhere(array &$where, $column, DB_Models_WritableModel_1 $model)
		{
			if(in_array($column, $model->getColumns()))
			{
				$value = $where[$column];
				unset($where[$column]);
				$where[$model->getTableName() . '.'. $column] = $value;
				return TRUE;
			}
			return FALSE;
		}
	}
?>