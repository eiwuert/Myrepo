<?php
/**
 * Class to handle our database instance connections.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Models_DatabaseInstanceHandler extends Object_1
{
	const ALIAS_WRITER = 'BLACKBOX_WRITER';
	const ALIAS_READER = 'BLACKBOX_READER';

	/**
	 * Map of the default databases to the alias we use for their connections.
	 *
	 * @var array
	 */
	private static $default_dbs = array(
		DB_Models_WritableModel_1::DB_INST_WRITE => self::ALIAS_WRITER,
		DB_Models_WritableModel_1::DB_INST_READ  => self::ALIAS_READER
	);

	/**
	 * Returns a database connection object for the given instance.
	 *
	 * @param string $db_inst a string of the database connection to retrieve
	 * @return DB_Database_1
	 */
	public function getDatabaseInstance($db_inst)
	{
		return $this->getRealDatabaseInstance(DB_Models_WritableModel_1::DB_INST_WRITE);

		// This is commented out until I find out if there's a reason we shouldn't use reader in
		// production - BrianF
//		$write_ref = $this->getRealDatabaseInstance(DB_Models_WritableModel_1::DB_INST_WRITE);
//
//		//return the master by default
//		if($db_inst === NULL || $db_inst === DB_Models_WritableModel_1::DB_INST_WRITE)
//			return $write_ref;
//
//		//this will need to change if we add a third type
//		$read_ref = $this->getRealDatabaseInstance(DB_Models_WritableModel_1::DB_INST_READ);
//
//		if($read_ref != $write_ref)
//			return $write_ref;
//
//		return $read_ref;
	}

	/**
	 * Returns the real database instance based on the default_dbs map.
	 *
	 * @param string $db_inst a string the database instance to connect to
	 * @return DB_Database_1
	 */
	protected function getRealDatabaseInstance($db_inst)
	{
		if (empty(self::$default_dbs[$db_inst]))
		{
			throw new Exception(__CLASS__ . " instance not found");
		}

		return DB_DatabaseConfigPool_1::getConnection(self::$default_dbs[$db_inst]);
	}
}
