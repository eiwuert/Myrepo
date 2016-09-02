<?php
/**
 * Test case for the DB_Connection object.
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class DB_ConnectionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testGetInstance().
	 *
	 * @return array
	 */
	public static function getInstanceDataProvider()
	{
		return array(
			array('olp', 'report'),
			array('BLACKBOX', 'RC'),
			array('olp', 'local'),
			array('ldb', 'RC', 'ca'),
			array('ecash', 'local', 'ic'),
			array('ldb', 'rc', 'tgc'),
			array('ECASH', 'RC', 'GENERIC')
			//array('ecash', 'LIVE', 'ufc'),
			//array('ECASH', 'LIVE_READONLY', 'ca'),
			//array('olp_session', 'LIVE'),
		);
	}
	
	/**
	 * Test that the getDBInfo function returns an array with the correct keys.
	 *
	 * @param string $type The database connection we want
	 * @param string $mode The mode we want the connection for
	 * @param string $property_short The property short
	 * 
	 * @dataProvider getInstanceDataProvider
	 * @return void
	 */
	public function testGetInstance($type, $mode, $property_short = NULL)
	{
		$this->markTestSkipped("This isn't really a good test, since it's actually making database connections");
		$connection = DB_Connection::getInstance($type, $mode, $property_short);
		
		$this->assertEquals(get_class($connection), 'DB_Database_1');
	}
	
	/**
	 * Test that the LIVE call actually returns the correct host.
	 *
	 * @return void
	 */
	/*public function testGetInstanceLive()
	{
		$connection = DB_Connection::getInstance('olp', 'live');
		
		$this->assertContains('writer.olp.ept.tss', $connection->getDSN());
	}*/
}
?>