<?php
require_once 'olp_lib_setup.php';

/**
 * Test case for the DBInfo_Enterprise object.
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DBInfo_EnterpriseTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testDBInfo().
	 *
	 * @return array
	 */
	public static function getDBInfoDataProvider()
	{
		return array(
			array('live','CBNK'),
			array('rc','CBNK'),
			array('local','CBNK'),
			array('LIVE','CBNK'),
			array('RC','CBNK'),
			array('LOCAL','CBNK'),
		);
	}
	
	/**
	 * Test that the getDBInfo function returns an array with the correct keys.
	 *
	 * @param string $property_short Property short of teh enterprise customer
	 * @param string $mode the mode we're testing
	 * @dataProvider getDBInfoDataProvider
	 * @return void
	 */
	public function testDBInfo($property_short,$mode)
	{
		$db_info = DBInfo_Enterprise::getDBInfo($property_short,$mode);
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
		$db_info = DBInfo_Enterprise::getDBInfo('CBNK','LIVE');
		
		$this->assertArrayHasKey('db', $db_info);
		$this->assertEquals('writer.ecashagean.ept.tss', $db_info['host']);
	}
}
?>
