<?php
/**
 * Test case for the EnterpriseData object.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class EnterpriseDataTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that we get back the live hostname for staging mode.
	 *
	 * @return void
	 */
	public function testGetEnterpriseHostNameStagingModeFromLive()
	{
		$this->assertEquals('live.ecash.eplatflat.com', EnterpriseData::getEnterpriseHostName('ca', 'staging'));
	}
}
?>
