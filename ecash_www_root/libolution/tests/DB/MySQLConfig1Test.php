<?php

/**
 * Test case for DB_MySQLConfig_1
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class DB_MySQLConfig1Test extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests the generated DSN
	 * @return void
	 */
	public function testDSN()
	{
		$c = new DB_MySQLConfig_1('test', 'user', 'pw');
		$this->assertEquals('mysql:host=test;port=3306;dbname=', $c->getDSN());
	}

	/**
	 * Tests the generated DSN with a schema name
	 * @return void
	 */
	public function testDSNWithSchema()
	{
		$c = new DB_MySQLConfig_1('test', 'user', 'pw', 'database');
		$this->assertEquals('mysql:host=test;port=3306;dbname=database', $c->getDSN());
	}

	/**
	 * Tests the generated DSN with a schema name
	 * @return void
	 */
	public function testDSNWithPort()
	{
		$c = new DB_MySQLConfig_1('test', 'user', 'pw', NULL, 1000);
		$this->assertEquals('mysql:host=test;port=1000;dbname=', $c->getDSN());
	}
	
	/**
	 * Test that we set the driver options correctly.
	 *
	 * @return void
	 */
	public function testDriverOptionsSet()
	{
		$options = array('ATTR_TIMEOUT' => 5);
		$c = new DB_MySQLConfig_1('test', 'foo', 'bar', NULL, 3306, $options);
		$this->assertEquals($options, $c->getDriverOptions());
	}
}

?>