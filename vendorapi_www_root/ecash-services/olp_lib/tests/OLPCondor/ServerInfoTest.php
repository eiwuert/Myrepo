<?php
/**
 * Test case for the OLPCondor_ServerInfo object.
 *
 * @author Adam L. Englander <adam.englander@sellingsource.com>
 */
class OLPCondor_ServerInfoTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * Test that the getServerInfo function returns the proper connection string
	 * in LIVE mode against a test property_short
	 *
	 * @return void
	 */
	public function testLiveServer()
	{
		$server_info = OLPCondor_ServerInfo::getServerInfo('TEST','LIVE');
		$this->assertEquals('testLiveKey:testLivePass@condor.4.internal.edataserver.com',$server_info);
	}
	
	/**
	 * Test that the getServerInfo function returns the proper connection string
	 * in RC mode against a test property_short
	 *
	 * @return void
	 */
	public function testRCServer()
	{
		$server_info = OLPCondor_ServerInfo::getServerInfo('TEST','RC');
		$this->assertEquals('testRCKey:testRCPass@rc.condor.4.edataserver.com',$server_info);
	}
	
	/**
	 * Test that the getServerInfo function returns the proper connection string
	 * in LOCAL mode against a test property_short
	 *
	 * @return void
	 */
	public function testLocalServer()
	{
		$server_info = OLPCondor_ServerInfo::getServerInfo('TEST','LOCAL');
		$this->assertEquals('testRCKey:testRCPass@rc.condor.4.edataserver.com',$server_info);
	}
	
	/**
	 * Test that the getServerInfo function returns the proper connection string
	 * based upon the property short
	 *
	 * @return void
	 */
	public function testPropertyShortServer()
	{
		$server_info = OLPCondor_ServerInfo::getServerInfo('TESTSERVER', 'LIVE');
		$this->assertEquals('testserverLiveKey:testserverLivePass@fake.condor.4.edataserver.com', $server_info);
	}
}
?>
