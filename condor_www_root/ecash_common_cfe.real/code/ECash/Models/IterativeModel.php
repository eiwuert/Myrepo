<?php

	abstract class ECash_Models_IterativeModel extends DB_Models_IterativeModel_1
	{
		/**
		 * @var DB_IConnection_1
		 */
		private $db;

		public function __construct($db = DB_Models_DatabaseModel_1::DB_INST_WRITE)
		{
			$this->db = $db;
		}

		public function setDatabaseInstance(DB_IConnection_1 $db)
		{
			$this->db = $db;
		}

		public function getDatabaseInstance($db_inst = DB_Models_DatabaseModel_1::DB_INST_WRITE)
		{
			return $this->db;
		}

        public function createInstance(array $db_row, array $override_dbs = NULL)
		{
			$name = $this->getClassName();
			$model = new $name();
			$model->setOverrideDatabases($override_dbs);
			$model->fromDbRow($db_row);

			return $model;
		}
		
		/**
		 * Returns the row that the cursor is currently pointing at.
		 *
		 * @return PDO row
		 */
		public function current_row()
		{
			if ($this->statement === null)
				throw new Exception("No rows available!");

			return $this->current === false ? null : $this->current;
		}

		/**
		 * Fetches the next item in the database, and updates the cursor
		 *
		 * @return DB_Models_ModelBase
		 */
		public function next_row()
		{
			if ($this->statement === null)
				throw new Exception("No rows available!");

			$this->current = $this->statement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT);

			return $this->current === false ? null : $this->current;
		}
	
		
	}

?>