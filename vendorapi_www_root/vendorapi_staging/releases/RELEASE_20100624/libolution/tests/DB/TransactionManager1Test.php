<?php

/**
 * Test case for DB_TransactionManager_1
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class DB_TransactionManager1Test extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that a transaction is created if one is not in progress
	 * @return void
	 */
	public function testTransactionIsCreatedWhenNoneInProgress()
	{
		$db = $this->getConnectionMock();

		$db->expects($this->any())
			->method('getInTransaction')
			->will($this->returnValue(FALSE));

		$db->expects($this->once())
			->method('beginTransaction');

		$tm = new DB_TransactionManager_1($db);
		$tm->beginTransaction();
	}

	/**
	 * Tests that a created transaction is committed
	 * @return void
	 */
	public function testCreatedTransactionIsCommitted()
	{
		$db = $this->getConnectionMock();

		$db->expects($this->any())
			->method('getInTransaction')
			->will($this->returnValue(FALSE));

		$db->expects($this->once())
			->method('commit');

		$tm = new DB_TransactionManager_1($db);
		$tm->beginTransaction();
		$tm->commit();
	}

	/**
	 * Tests that beginTransaction is not called twice
	 * @return void
	 */
	public function testTransactionInProgressIsUsed()
	{
		$db = $this->getConnectionMock();

		$db->expects($this->any())
			->method('getInTransaction')
			->will($this->returnValue(TRUE));

		$db->expects($this->never())
			->method('beginTransaction');

		$tm = new DB_TransactionManager_1($db);
		$tm->beginTransaction();
	}

	/**
	 * Tests that a borrowed transaction is never committed
	 * @return void
	 */
	public function testBorrowedTransactionIsNotCommitted()
	{
		$db = $this->getConnectionMock();

		$db->expects($this->any())
			->method('getInTransaction')
			->will($this->returnValue(TRUE));

		$db->expects($this->never())
			->method('commit');

		$tm = new DB_TransactionManager_1($db);
		$tm->beginTransaction();
		$tm->commit();
	}

	/**
	 * Tests that when borrowed transaction is rolled back, an exception is thrown
	 * @return void
	 */
	public function testBorrowedTransactionRollbackThrowsException()
	{
		$db = $this->getConnectionMock();

		$db->expects($this->any())
			->method('getInTransaction')
			->will($this->returnValue(TRUE));

		$db->expects($this->once())
			->method('rollBack');

		$tm = new DB_TransactionManager_1($db);
		$tm->beginTransaction();

		$this->setExpectedException('DB_TransactionAbortedException_1');
		$tm->rollBack();
	}

	/**
	 * Returns a mock of the DB_IConnection_1 object
	 *
	 * @return DB_IConnection_1
	 */
	protected function getConnectionMock()
	{
		return $this->getMock(
				'DB_IConnection_1',
				array(
					'prepare', 'query', 'exec', 'beginTransaction',
					'getInTransaction', 'commit', 'rollBack',
					'lastInsertId', 'quote', 'quoteObject',
				)
		);
	}

	/**
	 * Gets a DB_IConnection_1 instance for testing
	 * @return DB_IConnection_1
	 */
	protected function getConnection()
	{
		return new DB_Database_1('sqlite::memory:');
	}
}

?>
