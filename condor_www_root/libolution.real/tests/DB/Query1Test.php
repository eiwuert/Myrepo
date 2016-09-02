<?php

/**
 * Test case for DB_Query_1
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class DB_Query1Test extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that DB_Query_1::execute() calls DB_IConnection_1::query when there are no params
	 * @return void
	 */
	public function testExecuteCallsQueryWithNoParams()
	{
		$query = 'SELECT * FROM test';

		$c = $this->getConnectionMock();
		$c->expects($this->once())
			->method('query')
			->with($query);

		$q = new DB_Query_1($query);
		$q->execute($c);
	}

	/**
	 * Tests that DB_Query_1::execute() calls DB_IConnection_1::prepare()/execute() when there are params
	 * @return void
	 */
	public function testExecuteCallsPrepareWithParams()
	{
		$query = 'SELECT * FROM test WHERE name = ?';
		$params = array('test');

		$st = $this->getStatementMock();
		$st->expects($this->once())
			->method('execute')
			->with($this->equalTo($params));

		$c = $this->getConnectionMock();
		$c->expects($this->once())
			->method('prepare')
			->with($query)
			->will($this->returnValue($st));

		$q = new DB_Query_1($query, $params);
		$q->execute($c);
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
	 * Returns a mock of the DB_IStatement_1 object
	 *
	 * @return DB_IStatement_1
	 */
	protected function getStatementMock()
	{
		return $this->getMock(
				'StatementMock',
				array('execute', 'fetch', 'fetchAll', 'rowCount', 'getIterator')
		);
	}
}

?>
