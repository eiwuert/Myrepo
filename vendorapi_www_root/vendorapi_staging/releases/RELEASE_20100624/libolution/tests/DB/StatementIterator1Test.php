<?php

/**
 * Test case for DB_StatementIterator_1
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class DB_StatementIterator1Test extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that getStatement() returns an instance of DB_IStatement_1
	 *
	 */
	public function testGetStatementReturnsIStatement1Instance()
	{
		$st = $this->getStatementMock();
		$i = new DB_StatementIterator_1($st);
		$this->assertType('DB_IStatement_1', $i->getStatement());
	}

	/**
	 * Tests that rewind calls fetch()
	 * @return void
	 */
	public function testRewindCallsFetch()
	{
		$st = $this->getStatementMock();
		$st->expects($this->once())
			->method('fetch');

		$i = new DB_StatementIterator_1($st);
		$i->rewind();
	}

	/**
	 * Tests that next calls fetch()
	 * @return void
	 */
	public function testNextCallsFetch()
	{
		$st = $this->getStatementMock();
		$st->expects($this->once())
			->method('fetch');

		$i = new DB_StatementIterator_1($st);
		$i->next();
	}

	/**
	 * Tests that valid returns true if there are rows
	 * @return void
	 */
	public function testValidReturnsTrueWhenFetchReturnsRow()
	{
		$st = $this->getStatementMock();
		$st->expects($this->any())
			->method('fetch')
			->will($this->returnValue(array('test')));

		$i = new DB_StatementIterator_1($st);
		$i->rewind();

		$this->assertTrue($i->valid());
	}

	/**
	 * Tests that valid returns false once fetch() does
	 * @return void
	 */
	public function testValidReturnsFalseWhenFetchReturnsFalse()
	{
		$st = $this->getStatementMock();
		$st->expects($this->any())
			->method('fetch')
			->will($this->returnValue(FALSE));

		$i = new DB_StatementIterator_1($st);
		$i->rewind();

		$this->assertFalse($i->valid());
	}

	/**
	 * Tests that all the rows are being returned...
	 * @return void
	 */
	public function testDoesntSkipNothing()
	{
		$st = $this->getStatementMock();
		$st->expects($this->exactly(4))
			->method('fetch')
			->will($this->onConsecutiveCalls(
				array('test' => 1),
				array('test' => 2),
				array('test' => 3),
				FALSE
			));

		$iterator = new DB_StatementIterator_1($st);
		$i = 1;
		foreach ($iterator as $row)
		{
			$this->assertEquals($i++, $row['test']);
		}
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