<?php

/**
 * @package DB
 */

/**
 * Factory for acquiring connections to a database.
 *
 * @author John Hargrove <john.hargrove@sellingsource.com>
 */
interface DB_IDatabaseConfig_1
{
	/**
	 * Establishes a connection to the database.
	 *
	 * There is no guarantee that the DB_IConnection_1 instance
	 * from this method will actually hold a live connection to the
	 * database. For instance, some implementations have chosen to
	 * lazily connect upon first use.
	 *
	 * @return DB_IConnection_1
	 */
	public function getConnection();
}
