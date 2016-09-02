<?php

	interface ECash_Models_IDatabaseInstanceHandler
	{
		public static function setDefaultDatabaseInstance($db_inst, $alias);
		public function setDatabaseInstance($db_inst, $alias);
		public function setOverrideDatabases(array $override_dbs = NULL);
	}