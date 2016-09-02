<?php
require_once 'olp_lib_setup.php';

/**
 * Test case for the DBInfo_OLP object.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class DBInfo_OLPTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testDBInfo().
	 *
	 * @return array
	 */
	public static function getDBInfoDataProvider()
	{
		return array(
			array('live'),
			array('rc'),
			array('local'),
			array('LIVE'),
			array('RC'),
			array('LOCAL'),
		);
	}
	
	/**
	 * Test that the getDBInfo function returns an array with the correct keys.
	 *
	 * @param string $mode the mode we're testing
	 * @dataProvider getDBInfoDataProvider
	 * @return void
	 */
	public function testDBInfo($mode)
	{
		$db_info = DBInfo_OLP::getDBInfo($mode);
		$this->assertArrayHasKey('db', $db_info);
		$this->assertArrayHasKey('password', $db_info);
		$this->assertArrayHasKey('user', $db_info);
		$this->assertArrayHasKey('host', $db_info);
	}
	
	/**
	 * Test that the LIVE call actually returns the correct host.
	 *
	 * @return void
	 */
	public function testDBInfoLive()
	{
		$db_info = DBInfo_OLP::getDBInfo('LIVE');
		
		$this->assertArrayHasKey('db', $db_info);
		$this->assertEquals('writer.olp.ept.tss', $db_info['host']);
	}
}
?>
