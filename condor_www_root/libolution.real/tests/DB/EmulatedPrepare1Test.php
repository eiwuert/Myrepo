<?php

	require_once './autoload_setup.php';

	class DB_EmulatedPrepare1Test extends PHPUnit_Framework_TestCase
	{
		public static function queryProvider()
		{
			return array(
				array(
					'SELECT * FROM test WHERE name = :name',
					array('name' => 'test'),
					"SELECT * FROM test WHERE name = 'test'",
				),

				array(
					'SELECT * FROM test WHERE name = ?',
					array('test'),
					"SELECT * FROM test WHERE name = 'test'",
				),

				array(
					'INSERT INTO test VALUES (:name)',
					array('name' => 'test'),
					"INSERT INTO test VALUES ('test')",
				),

				array(
					'INSERT INTO test VALUES (?)',
					array('test'),
					"INSERT INTO test VALUES ('test')",
				),

				array(
					'INSERT INTO test VALUES (:test)',
					array('test' => 10),
					"INSERT INTO test VALUES ('10')",
				),

				array(
					'INSERT INTO test VALUES (?)',
					array(10),
					"INSERT INTO test VALUES ('10')",
				),

				array(
					'INSERT INTO test VALUES (:woot)',
					array('woot' => NULL),
					"INSERT INTO test VALUES (NULL)",
				),

				array(
					'INSERT INTO test VALUES (?)',
					array(NULL),
					"INSERT INTO test VALUES (NULL)",
				),

				// we're using SQLite to test; use their escaping...
				array(
					'INSERT INTO test VALUES (?)',
					array("test's"),
					"INSERT INTO test VALUES ('test''s')",
				),
			);
		}

		/**
		 * Tests the emulated preparation
		 *
		 * @dataProvider queryProvider
		 *
		 * @param string $query
		 * @param array $params
		 * @param string $expected
		 * @return void
		 */
		public function testPrepare($query, array $params, $expected)
		{
			$db = new DB_Database_1('sqlite:/tmp/test');

			$p = new DB_EmulatedPrepare_1($db, $query);
			$this->assertEquals($expected, $p->getQuery($params));
		}

		/**
		 * Tests that mixing parameters throws an exception
		 *
		 */
		public function testMixedInParamsThrowsException()
		{
			$query = "SELECT * FROM test WHERE col1=? and col2=?";
			$p = new DB_EmulatedPrepare_1($this->getMockConnection(), $query);

			$this->setExpectedException('Exception');
			$p->getQuery(array('woot' => '', 'test'));
		}

		/**
		 * Tests that mixing parameters in the query throws an exception
		 *
		 */
		public function testMixedInQueryThrowsException()
		{
			$query = "SELECT * FROM test WHERE col1=:woot and col2=?";

			$this->setExpectedException('Exception');
			$p = new DB_EmulatedPrepare_1($this->getMockConnection(), $query);
		}

		/**
		 * Tests that passing in named params with a query that has indexed params throws an exception
		 *
		 */
		public function testIndexedInQueryNamedInParamsThrowsException()
		{
			$query = "SELECT * FROM test WHERE col1=? and col2=?";
			$p = new DB_EmulatedPrepare_1($this->getMockConnection(), $query);

			$this->setExpectedException('Exception');
			$p->getQuery(array('woot' => '', 'blah' => 'test'));
		}

		/**
		 * Tests that passing in indexed params with a query that has named params throws an exception
		 *
		 */
		public function testNamedInQueryIndexedInParamsThrowsException()
		{
			$query = "SELECT * FROM test WHERE col1=:col1 and col2=:col2";
			$p = new DB_EmulatedPrepare_1($this->getMockConnection(), $query);

			$this->setExpectedException('Exception');
			$p->getQuery(array('woot', 'test'));
		}

		/**
		 * Ensures that using the same named parameter more than once throws an exception
		 *
		 */
		public function testReusingNamedParamThrowsException()
		{
			$query = "SELECT * FROM test WHERE col1=:woot and col2=:woot";
			$p = new DB_EmulatedPrepare_1($this->getMockConnection(), $query);

			$this->setExpectedException('Exception');
			$p->getQuery(array('woot' => ''));
		}

		/**
		 * Ensures that using the same named parameter more than once throws an exception
		 *
		 */
		public function testMissingNamedParamThrowsException()
		{
			$query = "SELECT * FROM test WHERE col1=:woot and col2=:foo";
			$p = new DB_EmulatedPrepare_1($this->getMockConnection(), $query);

			$this->setExpectedException('Exception');
			$p->getQuery(array('woot' => ''));
		}

		/**
		 * Ensures that using the same named parameter more than once throws an exception
		 *
		 */
		public function testMissingParamThrowsException()
		{
			$query = "SELECT * FROM test WHERE col1=? and col2=?";
			$p = new DB_EmulatedPrepare_1($this->getMockConnection(), $query);

			$this->setExpectedException('Exception');
			$p->getQuery(array(''));
		}

		/**
		 * Returns a mock of the DB_IConnection_1 object
		 *
		 * @return DB_IConnection_1
		 */
		protected function getMockConnection()
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
	}

?>
