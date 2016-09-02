<?php

	/**
	 * Reimplimentation of the database instance handling code Justin wrote for the ecash models
	 *
	 * @author John Hargrove
	 */
	class ECash_Models_DatabaseInstanceHandler extends Object_1 implements ECash_Models_IDatabaseInstanceHandler
	{
		const ALIAS_MASTER = 'ECASH_MASTER';
		const ALIAS_SLAVE = 'ECASH_SLAVE';

		private static $default_dbs = array(DB_Models_WritableModel_1::DB_INST_WRITE => self::ALIAS_MASTER,
											DB_Models_WritableModel_1::DB_INST_READ  => self::ALIAS_SLAVE);
		/**
		 * @param string $db_inst
		 * @param string $alias
		 */
		public static function setDefaultDatabaseInstance($db_inst, $alias)
		{
			self::$default_dbs[$db_inst] = $alias;
		}

		private $override_dbs;

		/**
		 * @param string $db_inst
		 * @param string $alias
		 */
		public function setDatabaseInstance($db_inst, $alias)
		{
			$this->override_dbs[$db_inst] = $alias;
		}

		/**
		 * @param string $db_inst
		 * @return DB_Database_1
		 */
		public function getDatabaseInstance($db_inst)
		{
			/**
			 * Only returning the master database connection.  Impact has been having
			 * stability problems with the slave and this would take the whole application 
			 * down.  That is a bad thing.  [BrianR]
			 */
			return $this->getRealDatabaseInstance(DB_Models_WritableModel_1::DB_INST_WRITE);
			
			$write_ref = $this->getRealDatabaseInstance(DB_Models_WritableModel_1::DB_INST_WRITE);

			//return the master by default
			if($db_inst === NULL || $db_inst === DB_Models_WritableModel_1::DB_INST_WRITE)
				return $write_ref;

			//this will need to change if we add a third type
			$read_ref = $this->getRealDatabaseInstance(DB_Models_WritableModel_1::DB_INST_READ);

			if($read_ref != $write_ref && $this->isSlaveBehindTooMuch())
				return $write_ref;

			return $read_ref;
		}

		/**
		 * @param string $db_inst
		 * @return DB_Database_1
		 */
		private function getRealDatabaseInstance($db_inst)
		{
			if(!empty($this->override_dbs[$db_inst]))
				return DB_DatabaseConfigPool_1::getConnection($this->override_dbs[$db_inst]);

			if(!empty(self::$default_dbs[$db_inst]))
				return DB_DatabaseConfigPool_1::getConnection(self::$default_dbs[$db_inst]);

			throw new Exception(__CLASS__ . " instance not found.");
		}

		/**
		 * @return boolean
		 */
		private function isSlaveBehindTooMuch()
		{
			return TRUE;
		}

		/**
		 * @param array $override_dbs
		 */
		public function setOverrideDatabases(array $override_dbs = NULL)
		{
			if(is_array($override_dbs))
			{
				foreach($override_dbs as $db_inst => $alias)
				{
					$this->setDatabaseInstance($db_inst, $alias);
				}
			}
		}

	}
?>