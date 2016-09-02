<?php

require_once 'autoload_setup.php';

/**
 * Test cases for the DB_Util_1 Class.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class DB_Util1Test extends PHPUnit_Framework_TestCase
{
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

	/**
	 * Returns data for testQueryPrepared
	 *
	 * @return array
	 */
	public static function dataTestQueryPrepared()
	{
		return array(
			array('SELECT * FROM agent WHERE agent_id = ?', array(1)),
			array('SELECT * FROM event_type', array()),
		);
	}

	/**
	 * Tests the DB_Util_1::queryPrepared() method.
	 *
	 * @param string $query
	 * @param array $args
	 * @dataProvider dataTestQueryPrepared
	 */
	public function testQueryPrepared($query, Array $args)
	{
		$mock_statement = $this->getStatementMock();

		$mock_statement->expects($this->once())
				->method('execute')
				->with($this->equalTo($args));

		$mock_connection = $this->getConnectionMock();

		$mock_connection->expects($this->once())
				->method('prepare')
				->with($this->equalTo($query))
				->will($this->returnValue($mock_statement));


		$this->assertSame(
				$mock_statement,
				DB_Util_1::queryPrepared($mock_connection, $query, $args)
		);
	}

	/**
	 * Returns data for testQueryPrepared
	 *
	 * @return array
	 */
	public static function dataTestExecPrepared()
	{
		return array(
			array('UPDATE agent SET status = ? WHERE agent_id = ?', array('inactive', 1), 1),
			array('DELETE FROM event_type', array(), 100),
			array('DELETE FROM event_type WHERE company_id = ?', array(3), 30),
		);
	}

	/**
	 * Tests the DB_Util_1::queryPrepared() method.
	 *
	 * @param string $query
	 * @param array $args
	 * @param int $row_count
	 * @dataProvider dataTestExecPrepared
	 */
	public function testExecPrepared($query, Array $args, $row_count)
	{
		$mock_statement = $this->getStatementMock();

		$mock_statement->expects($this->once())
				->method('execute')
				->with($this->equalTo($args));

		$mock_statement->expects($this->once())
				->method('rowCount')
				->will($this->returnValue($row_count));

		$mock_connection = $this->getConnectionMock();

		$mock_connection->expects($this->once())
				->method('prepare')
				->with($this->equalTo($query))
				->will($this->returnValue($mock_statement));

		$this->assertSame(
				$row_count,
				DB_Util_1::execPrepared($mock_connection, $query, $args)
		);
	}

	/**
	 * Returns data for testQuerySingleValuePrepared
	 *
	 * @return array
	 */
	public static function dataTestQuerySingleValuePrepared()
	{
		return array(
			array('SELECT * FROM agent WHERE agent_id = ?', array(1), 3,
				array(1, 'Mike', 'Lively', 'mlively', 'active'),
				'mlively'
			),
			array('SELECT * FROM event_type', array(), 0,
				array(1, 'loan_disbursement', 'active'),
				1
			),
		);
	}

	/**
	 * Tests the DB_Util_1::querySingleValue() method.
	 *
	 * @param string $query
	 * @param array $args
	 * @param int $column_number
	 * @param array $all_columns
	 * @param mixed $column_value
	 * @dataProvider dataTestQuerySingleValuePrepared
	 */
	public function testQuerySingleValuePrepared($query, $args, $column_number, $all_columns, $column_value)
	{
		$mock_statement = $this->getStatementMock();

		$mock_statement->expects($this->once())
				->method('fetch')
				->with($this->equalTo(DB_IStatement_1::FETCH_ROW))
				->will($this->returnValue($all_columns));

		$mock_statement->expects($this->once())
				->method('execute')
				->with($this->equalTo($args));

		$mock_connection = $this->getConnectionMock();

		$mock_connection->expects($this->once())
				->method('prepare')
				->with($this->equalTo($query))
				->will($this->returnValue($mock_statement));

		$this->assertEquals(
			$column_value,
			DB_Util_1::querySingleValue($mock_connection, $query, $args, $column_number)
		);
	}

	/**
	 * Returns data for testQuerySingleValueQuery
	 *
	 * @return array
	 */
	public static function dataTestQuerySingleValueQuery()
	{
		return array(
			array('SELECT * FROM agent WHERE agent_id = 1', 2,
				array(1, 'Mike', 'Lively', 'mlively', 'active'),
				'Lively'
			),
			array('SELECT * FROM event_type', 1,
				array(1, 'loan_disbursement', 'active'),
				'loan_disbursement'
			),
		);
	}

	/**
	 * Tests the DB_Util_1::querySingleValue() method.
	 *
	 * @param string $query
	 * @param int $column_number
	 * @param array $all_columns
	 * @param mixed $column_value
	 * @dataProvider dataTestQuerySingleValueQuery
	 */
	public function testQuerySingleValueQuery($query, $column_number, $all_columns, $column_value)
	{
		$mock_statement = $this->getStatementMock();

		$mock_statement->expects($this->once())
				->method('fetch')
				->with($this->equalTo(DB_IStatement_1::FETCH_ROW))
				->will($this->returnValue($all_columns));

		$mock_connection = $this->getConnectionMock();

		$mock_connection->expects($this->once())
				->method('query')
				->with($this->equalTo($query))
				->will($this->returnValue($mock_statement));

		$this->assertEquals(
			$column_value,
			DB_Util_1::querySingleValue($mock_connection, $query, NULL, $column_number)
		);
	}

	/**
	 * Returns data for testQuerySingleColumn
	 *
	 * @return array
	 */
	public static function dataTestQuerySingleColumn()
	{
		return array(
			array('SELECT * FROM agent WHERE agent_id IN (?,?,?)', array(1, 2, 3), 2,
				array(
					array(1, 'Mike', 'Lively', 'mlively', 'active'),
					array(2, 'Bob', 'Dylan', 'bdylan', 'active'),
					array(3, 'John', 'Smith', 'jsmith', 'active')
				),
				array('Lively', 'Dylan', 'Smith')
			),
		);
	}

	/**
	 * Tests the DB_Util_1::querySingleColumn() method.
	 *
	 * @param string $query
	 * @param array $args
	 * @param int $column_number
	 * @param array $all_rows
	 * @param mixed $column_value
	 * @dataProvider dataTestQuerySingleColumn
	 */
	public function testQuerySingleColumn($query, $args, $column_number, $all_rows, $column_value)
	{
		$mock_statement = $this->getStatementMock();

		$mock_statement->expects($this->once())
				->method('fetchAll')
				->with($this->equalTo(DB_IStatement_1::FETCH_ROW))
				->will($this->returnValue($all_rows));

		$mock_statement->expects($this->once())
				->method('execute')
				->with($this->equalTo($args));

		$mock_connection = $this->getConnectionMock();

		$mock_connection->expects($this->once())
				->method('prepare')
				->with($this->equalTo($query))
				->will($this->returnValue($mock_statement));

		$this->assertEquals(
			$column_value,
			DB_Util_1::querySingleColumn($mock_connection, $query, $args, $column_number)
		);
	}

	/**
	 * Returns data for testQuerySingleRow
	 *
	 * @return array
	 */
	public static function dataTestQuerySingleRow()
	{
		return array(
			array('SELECT * FROM agent WHERE agent_id =?', array(1),
				DB_IStatement_1::FETCH_ASSOC,
				array(1, 'Mike', 'Lively', 'mlively', 'active')
			),
			array('SELECT * FROM agent WHERE agent_id =?', array(2),
				NULL,
				array(2, 'Bob', 'Dylan', 'bdylan', 'active')
			),
			array('SELECT * FROM agent WHERE agent_id =?', array(3),
				DB_IStatement_1::FETCH_OBJ,
				array(3, 'John', 'Smith', 'jsmith', 'active'),
				array('Lively', 'Dylan', 'Smith')
			),
		);
	}

	/**
	 * Tests the DB_Util_1::querySingleRow() method.
	 *
	 * @param string $query
	 * @param array $args
	 * @param int $fetch_mode
	 * @param array $row
	 * @dataProvider dataTestQuerySingleRow
	 */
	public function testQuerySingleRow($query, $args, $fetch_mode, $row)
	{
		$mock_statement = $this->getStatementMock();

		$mock_statement->expects($this->once())
				->method('fetch')
				->with($this->equalTo(is_null($fetch_mode) ? DB_IStatement_1::FETCH_ASSOC : $fetch_mode))
				->will($this->returnValue($row));

		$mock_statement->expects($this->once())
				->method('execute')
				->with($this->equalTo($args));

		$mock_connection = $this->getConnectionMock();

		$mock_connection->expects($this->once())
				->method('prepare')
				->with($this->equalTo($query))
				->will($this->returnValue($mock_statement));

		$this->assertEquals(
			$row,
			DB_Util_1::querySingleRow($mock_connection, $query, $args, $fetch_mode)
		);
	}

	/**
	 * Returns data for testBuildWhereClause
	 *
	 * @return array
	 */
	public static function dataTestBuildWhereClause()
	{
		return array(
			array(
				array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'),
				FALSE,
				' where key1 = ? and key2 = ? and key3 = ?'
			),
			array(
				array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'),
				TRUE,
				' where key1 = :key1 and key2 = :key2 and key3 = :key3'
			),
			array(
				array(),
				TRUE,
				''
			),
			array(
				array(),
				FALSE,
				''
			),
		);
	}

	/**
	 * Tests the DB_Util_1::buildWhereClause() method.
	 *
	 * @param array $where_args
	 * @param bool $named_params
	 * @param string $expected_clause
	 * @dataProvider dataTestBuildWhereClause
	 */
	public function testBuildWhereClause($where_args, $named_params, $expected_clause)
	{
		$this->assertEquals($expected_clause, DB_Util_1::buildWhereClause($where_args, $named_params));
	}


	/**
	 * Returns data for testBuildWhereClauseNoNamedParamArg
	 *
	 * @return array
	 */
	public static function dataTestBuildWhereClauseNoNamedParamArg()
	{
		return array(
			array(
				array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'),
				' where key1 = :key1 and key2 = :key2 and key3 = :key3'
			),
			array(
				array(),
				''
			),
		);
	}

	/**
	 * Tests the DB_Util_1::buildWhereClause() method with no $named_params
	 * argument.
	 *
	 * @param array $where_args
	 * @param string $expected_clause
	 * @dataProvider dataTestBuildWhereClauseNoNamedParamArg
	 */
	public function testBuildWhereClauseNoNamedParamArg($where_args, $expected_clause)
	{
		$this->assertEquals($expected_clause, DB_Util_1::buildWhereClause($where_args));
	}
}

?>
