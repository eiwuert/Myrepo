<?php
require_once 'olp_lib_setup.php';

/**
 * Test case for the DBInfo_StatPro object.
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DBInfo_StatProTest extends PHPUnit_Framework_TestCase
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
		$db_info = DBInfo_StatPro::getDBInfo($mode);
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
		$db_info = DBInfo_Statpro::getDBInfo('LIVE');
		
		$this->assertArrayHasKey('db', $db_info);
		$this->assertEquals('reporting.statpro2.ept.tss:3307', $db_info['host']);
	}
}
?>
