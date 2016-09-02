<?php

/**
 * A test case for DB_Database_1
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class DB_Database1Test extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that query() returns an instance of DB_Statement_1
	 * @return void
	 */
	public function testQueryReturnsStatement1Instance()
	{
		$st = $this->getConnection()
			->query('SELECT 1');
		$this->assertType('DB_IStatement_1', $st);
	}

	/**
	 * Tests that prepare() returns an instance of DB_Statement_1
	 * @return void
	 */
	public function testPrepareReturnsStatement1Instance()
	{
		$st = $this->getConnection()
			->prepare('SELECT 1');
		$this->assertType('DB_IStatement_1', $st);
	}

	/**
	 * Test that getInTransaction returns FALSE before a transaction has been started
	 * @return void
	 */
	public function testGetInTransactionReturnsFalseOutsideTransaction()
	{
		$db = $this->getConnection();
		$this->assertFalse($db->getInTransaction());
	}

	/**
	 * Test that getInTransaction returns TRUE once a transaction has been started
	 * @return void
	 */
	public function testGetInTransactionReturnsTrueInsideTransaction()
	{
		$db = $this->getConnection();
		$db->beginTransaction();

		$this->assertTrue($db->getInTransaction());
	}

	/**
	 * Test that getInTransaction returns FALSE when the transaction has been ended by commit()
	 * @return void
	 */
	public function testGetInTransactionReturnsFalseAfterCommit()
	{
		$db = $this->getConnection();

		$db->beginTransaction();
		$db->commit();

		$this->assertFalse($db->getInTransaction());
	}

	/**
	 * Test that getInTransaction returns FALSE when the transaction has been ended by rollBack()
	 * @return void
	 */
	public function testGetInTransactionReturnsFalseAfterRollback()
	{
		$db = $this->getConnection();

		$db->beginTransaction();
		$db->rollBack();

		$this->assertFalse($db->getInTransaction());
	}

	/**
	 * Gets a connection
	 * @return DB_IConnection_1
	 */
	protected function getConnection()
	{
		return new DB_Database_1('sqlite::memory:');
	}
}

?>