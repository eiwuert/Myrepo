<?php
/**
 * Test case for the auto campaign shutoff rule
 *
 * @author Eric Johney <eric.johney@sellingsource.com>
 */
class OLPBlackbox_Rule_CampaignShutoffTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * Ensures that the connection is destroyed
	 * @return void
	 */
	public function tearDown()
	{
		parent::tearDown();
		$this->databaseTester = NULL;
	}
	
	/**
	 * Returns a campaign shutoff rule that has the necessary mocked methods
	 * @return void
	 */
	protected function getMockRule($campaign_name, $query_date, $expected_value)
	{
		$rule = $this->getMock(
			'OLPBlackbox_Rule_CampaignShutoff',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitRuleEvent', 'hitRuleStat', 'checkCache', 'updateCache')
		);
		// Return our db instance
		$rule->expects($this->any())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Return our db name
		$rule->expects($this->any())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));
		// Return our specified date, expect our rule value
		$rule->expects($this->any())->method('getQueryDate')
			->will($this->returnValue($query_date));
		
		return $rule;
	}

	/**
	 * This will actuall be the data provider for both the 
	 * testIsValidCached and testIsValidNotCached methods
	 *
	 * @return array
	 */
	public static function isValidDataProvider()
	{
		return array(
			array('aaa', '2009-07-01 00:00:00', TRUE),
			array('aaa', '2009-06-30 01:00:00', FALSE),
			array('bbb', '2009-06-30 20:00:00', FALSE),
			array('bbb', '2009-07-01 03:59:59', FALSE),
			array('bbb', '2009-07-01 04:00:00', TRUE),
			array('ccc', '2009-06-30 19:00:00', TRUE),
			array('ccc', '2009-06-30 20:00:00', FALSE),
			array('ccc', '2009-06-30 20:00:01', FALSE),
			array('ccc', '2009-07-01 20:00:00', FALSE),
			array('zzz', '2009-07-01 00:00:00', TRUE),
		);
	}
	
	/**
	 * Wraps the isValidDataProvider
	 *
	 * @return array
	 */
	public static function isValidCachedDataProvider()
	{
		return self::isValidDataProvider();
	}
	
	/**
	 * Tests isValid for campaign shutoff assuming a cached result
	 * is found
	 * 
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValidCached($campaign_name, $query_date, $expected_value)
	{
		$rule = $this->getMockRule($campaign_name, $query_date, $expected_value);
			
		// Return mock result from memcache
		$rule->expects($this->once())->method('checkCache')
			->will($this->returnValue(!$expected_value));
		// make sure we don not call these methods since we have a cached value
		$rule->expects($this->never())->method('updateCache');
		$rule->expects($this->never())->method('checkDb');
		
		$valid = $rule->isValid(
			new OLPBlackbox_Data(), 
			new OLPBlackbox_CampaignStateData(array('campaign_name' => $campaign_name))
		);
		$this->assertSame($expected_value, $valid);	
	}
	
	/**
	 * Wraps the isValidDataProvider
	 *
	 * @return array
	 */
	public static function isValidNotCachedDataProvider()
	{
		return self::isValidDataProvider();
	}
	
	/**
	 * Tests isValid for campaign shutoff assuming the result is not cached
	 * and must be fetched from the database
	 * 
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValidNotCached($campaign_name, $query_date, $expected_value)
	{
		$rule = $this->getMockRule($campaign_name, $query_date, $expected_value);
		
		// Return mock result from memcache
		$rule->expects($this->once())->method('checkCache')
			->will($this->returnValue(NULL));
		// make sure we try to cache the result
		$rule->expects($this->once())->method('updateCache');
		
		$valid = $rule->isValid(
			new OLPBlackbox_Data(), 
			new OLPBlackbox_CampaignStateData(array('campaign_name' => $campaign_name))
		);
		$this->assertSame($expected_value, $valid);	
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
	 * Gets the data set for this test.
	 *
	 * @return PHPUnit_Extensions_Database_DataSet_XmlDataSet
	 */
	protected function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/CampaignShutoff.fixture.xml');
	}
}
?>
