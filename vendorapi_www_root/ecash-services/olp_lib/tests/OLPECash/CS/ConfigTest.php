<?php
/**
 * Test case for the OLPECash_CS_Config object.
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPECash_CS_ConfigTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Elements to check for from the cofig array and object
	 *
	 * @var array
	 */
	protected static $config_elements = array('url');
	
	/**
	 * Data provider for testGetConfig().
	 *
	 * @return array
	 */
	public static function getConfigDataProvider()
	{
		return array(
			array('live','mcc'),
			array('rc','mcc'),
			array('local','mcc'),
			array('LIVE','MCC'),
			array('RC','MCC'),
			array('LOCAL','MCC'),
		);
	}
	
	/**
	 * Test that the testGetRpcUrl function returns URL.
	 *
	 * @param string $mode the mode we're testing
	 * @param string $property_short Property short of the CSO company
	 * @dataProvider getConfigDataProvider
	 * @return void
	 */
	public function testGetRpcUrl($mode, $property_short)
	{
		$info = OLPECash_CS_Config::getRpcUrl($property_short, $mode, 'eCash_Custom_RPC_CSO');
		$this->assertNotNull($info);
	}
	
	/**
	 * Test that the getCSORpcUrl function returns URL.
	 *
	 * @param string $mode the mode we're testing
	 * @param string $property_short Property short of the CSO company
	 * @dataProvider getConfigDataProvider
	 * @return void
	 */
	public function testGetCSORpcUrl($mode, $property_short)
	{
		$info = OLPECash_CS_Config::getCSORpcUrl($property_short,$mode);
		$this->assertNotNull($info);
	}
	
	
	/**
	 * Test that the getTokenRpcUrl function returns URL.
	 *
	 * @param string $mode the mode we're testing
	 * @param string $property_short Property short of a company
	 * @dataProvider getConfigDataProvider
	 * @return void
	 */
	public function testGetTokenRpcUrl($mode, $property_short)
	{
		$info = OLPECash_CS_Config::getTokenRpcUrl($property_short,$mode);
		$this->assertNotNull($info);
	}
	
	
	/**
	 * Test the canRollover function.
	 *
	 * @return void
	 */
	public function testCanRollover()
	{
		$this->assertTRUE(OLPECash_CS_Config::canRollover('MCC','RC'));
		$this->assertFALSE(OLPECash_CS_Config::canRollover('___','RC'));
		
	}
	
}
?>
