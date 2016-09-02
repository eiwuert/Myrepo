<?php
/**
 * Tests for the OLP_DB_MySQL4Adapter class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLP_DB_MySQL4AdapterTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that we get a MySQL_Exception returned instead of a PDOException.
	 *
	 * @return void
	 */
	public function testQueryException()
	{
		$db = $this->getMock('DB_Database_1', array(), array(), '', FALSE);
		$db->expects($this->any())
			->method('query')
			->will($this->throwException(new PDOException('A test error.')));
		
		$this->setExpectedException('MySQL_Exception');
		
		$db_adapt = OLP_DB_MySQL4Adapter::fromConnection($db);
		$db_adapt->Query(NULL, "SELECT 1");
	}
}
