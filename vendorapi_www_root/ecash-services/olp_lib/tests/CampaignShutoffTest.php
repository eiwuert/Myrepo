<?php

/**
 * Tests the logic for looking for campaigns to shut off
 *
 * @author Eric Johney <eric.johney@sellingsource.com>
 */
class CampaignShutoffTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * make sure the campaign_shutoff table is cleared before we run this test
	 *
	 * @return void
	 */
	public function setUp()
	{
		TEST_DB_PDO()->query("DELETE FROM campaign_shutoff");
		parent::setUp();
	}
	
	/**
	 * data provider for testRunChecks
	 *
	 * @return array
	 */
	public static function runChecksDataProvider()
	{
		return array(
			array('aaa', '2009-07-08 10:00:00', FALSE, FALSE),	// 0% accept rate, but no timeouts
			array('aaa', '2009-07-08 11:00:00', FALSE, FALSE),	// 50% accept rate, but no timeouts
			array('aaa', '2009-07-08 11:30:00', FALSE, FALSE),	// 50% accept rate, and 1 timeout
			array('aaa', '2009-07-08 12:00:00', '2009-07-08 15:00:00', 'Accept Rate = 33.3% AND Timeouts/Errors/Blanks = 2'),	// 33.3% accept rate, and 2 timeouts
		);
	}
	
	/**
	 * run the test
	 *
	 * @dataProvider runChecksDataProvider
	 * @param string $campaign_name
	 */
	public function testRunChecks($campaign_name, $current_time, $expected_activated_at, $expected_value)
	{
		$campaign_shutoff = $this->getMock(
			'CampaignShutoff',
			array('getCampaigns', 'getCurrentTimestamp'),
			array(TEST_DB_PDO(), TEST_DB_PDO())
		);
		
		$campaign_shutoff->expects($this->any())->method('getCampaigns')
			->will($this->returnValue(array($campaign_name)));
		$campaign_shutoff->expects($this->any())->method('getCurrentTimestamp')
			->will($this->returnValue(strtotime($current_time)));
		
		$result = $campaign_shutoff->runChecks($campaign_name);
		
		if ($expected_value === FALSE)
		{
			$this->assertTrue(empty($result));
		}
		else
		{
			$disabled_campaign = $result;
			
			$this->assertTrue(count($disabled_campaign) == 1);			
			$this->assertSame($campaign_name, array_pop(array_keys($disabled_campaign)));
			$this->assertSame($expected_value, array_pop($disabled_campaign)->reason);
		}
		
		// test that the insert works and marks the correct time for reactivation
		if (!empty($result))
		{
			$campaign_shutoff->disableCampaigns();
			$activated_at = TEST_DB_PDO()->query("
				SELECT activated_at FROM campaign_shutoff WHERE property_short = '$campaign_name'
			")->fetchColumn();
			TEST_DB_PDO()->query("DELETE FROM campaign_shutoff");
			$this->assertSame($expected_activated_at, $activated_at);
		}
	}
		
	/**
	 * Gets the database connection for this test.
	 *
	 * @return PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
	 */
	protected function getConnection()
	{
		return $this->createDefaultDBConnection(TEST_DB_PDO(), TEST_GET_DB_INFO()->name);
	}
	
	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet 
	 * @see PHPUnit_Extensions_Database_TestCase::getDataSet()
	 */
	protected function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/CampaignShutoff.xml');
	}
}

?>