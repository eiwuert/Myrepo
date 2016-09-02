<?php
/**
 * Test case for the DB_Server object.
 *
  * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class DB_ServerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testGetServer().
	 *
	 * @return array
	 */
	public static function getServerDataProvider()
	{
		return array(
			array('olp', 'report'),
			array('BLACKBOX', 'RC'),
			array('olp', 'local'),
			array('ecash', 'LIVE', 'ufc'),
			array('ldb', 'RC', 'ca'),
			array('ECASH', 'LIVE_READONLY', 'ca'),
			array('olp_session', 'LIVE'),
			array('management', 'rc'),
		);
	}
	
	/**
	 * Data provider for testGetServerCondor()
	 * 
	 * @return array
	 */
	public static function getServerCondorDataProvider()
	{
		return array(
			array('rc', 'ucl'),
			array('LIVE', 'UCL'),
			array('LIVE', 'ace'),
			array('local', 'ufc'),
			array('local', 'ca'),
			array('rc', 'd1'),
			array('rc', 'ic'),
			array('live', 'qeasy'),
			array('rc', 'tgc'),
			array('local', 'obb'),
			array('live', 'lcs'),
			array('rc', 'generic')
		);
	}
	
	/**
	 * Test that the getDBInfo function returns an array with the correct keys.
	 *
	 * @param string $type The database connection we want
	 * @param string $mode The mode we want the connection for
	 * @param string $property_short The property short
	 * 
	 * @dataProvider getServerDataProvider
	 * @return void
	 */
	public function testGetServer($type, $mode, $property_short = NULL)
	{
		$db_info = DB_Server::getServer($type, $mode, $property_short);
		
		$this->assertArrayHasKey('db', $db_info->getArrayCopy());
		$this->assertArrayHasKey('password', $db_info->getArrayCopy());
		$this->assertArrayHasKey('user', $db_info->getArrayCopy());
		$this->assertArrayHasKey('host', $db_info->getArrayCopy());
	}
	
	/**
	 * Tests that the LDB connections default to UFC
	 *
	 * @return void
	 */
	public function testGetServerECashDefault()
	{
		$db_info = DB_Server::getServer('ecash', 'live', 'not_a_real_company');
		
		$this->assertArrayHasKey('db', $db_info->getArrayCopy());
		$this->assertArrayHasKey('password', $db_info->getArrayCopy());
		$this->assertArrayHasKey('user', $db_info->getArrayCopy());
		$this->assertArrayHasKey('host', $db_info->getArrayCopy());
		$this->assertEquals($db_info['host'], 'writer.ecashufc.ept.tss');
	}
	
	/**
	 * Tests condor connection information
	 *
	 * @param string $mode The mode we want the connection for
	 * @param string $property_short The property short
	 * 
	 * @dataProvider getServerCondorDataProvider
	 * @return void
	 */
	public function testGetServerCondor($mode, $property_short)
	{
		$condor_info = DB_Server::getServer('CONDOR', $mode, $property_short);
		
		$this->assertRegExp('/^(\S+)\:(\S+)@(rc\.)?condor\.4\.(internal\.)?edataserver\.com$/', $condor_info);
	}
	
	/**
	 * Test that the LIVE call actually returns the correct host.
	 *
	 * @return void
	 */
	public function testGetServerLive()
	{
		$db_info = DB_Server::getServer('olp', 'LIVE');
		
		$this->assertArrayHasKey('db', $db_info->getArrayCopy());
		$this->assertEquals('writer.olp.ept.tss', $db_info['host']);
	}
}
?>
