<?php
/**
 * Test case for the brick and mortar rule
 *
 * @author Eric Johney <eric.johney@sellingsource.com>
 */
class OLPBlackbox_Rule_BrickAndMortarTest extends PHPUnit_Extensions_Database_TestCase
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
	 * Returns a brick and mortar rule that has the necessary mocked methods
	 * @return void
	 */
	protected function getMockRule($campaign_name, $home_zip, $expected_value)
	{
		$rule = $this->getMock(
			'OLPBlackbox_Rule_BrickAndMortarZipCode',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitRuleEvent', 'hitRuleStat', 'getDataValue', 'checkCache', 'updateCache')
		);
		// Return our db instance
		$rule->expects($this->any())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Return our db name
		$rule->expects($this->any())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));
		
		$rule->expects($this->any())->method('getDataValue')
			->will($this->returnValue($home_zip));
		
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
			array('aaa', 11111, TRUE),
			array('aaa', 22222, TRUE),
			array('bbb', 22222, FALSE),
			array('bbb', 33333, TRUE),
			array('zzz', 99999, FALSE),
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
	 * Tests isValid for brick and mortar assuming a cached result
	 * is found
	 * 
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValidCached($campaign_name, $home_zip, $expected_value)
	{
		$rule = $this->getMockRule($campaign_name, $home_zip, $expected_value);
			
		// Return mock result from memcache
		if ($expected_value)
		{
			$rule->expects($this->once())->method('checkCache')
				->will($this->returnValue(array(array(
					'zip_code' => $home_zip,
					'property_short' => $campaign_name
				))));
		}
		else
		{
			$rule->expects($this->once())->method('checkCache')
				->will($this->returnValue(NULL));
		}
		// make sure we don not call these methods since we have a cached value
		$rule->expects($this->never())->method('updateCache');
		$rule->expects($this->never())->method('checkDb');
		
		$state_data = new OLPBlackbox_CampaignStateData(array('campaign_name' => $campaign_name));
		$valid = $rule->isValid(
			new OLPBlackbox_Data(array('home_zip' => $home_zip)), 
			$state_data
		);
		$this->assertSame($expected_value, $valid);
		
		// make sure the store got inserted into the state data correctly
		if ($expected_value)
		{
			$this->assertSame($home_zip, intval($state_data->brick_and_mortar_store['zip_code']));
		}
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
	 * Tests isValid for brick and mortar assuming the result is not cached
	 * and must be fetched from the database
	 * 
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValidNotCached($campaign_name, $home_zip, $expected_value)
	{
		$rule = $this->getMockRule($campaign_name, $home_zip, $expected_value);
		
		// Return mock result from memcache
		$rule->expects($this->once())->method('checkCache')
			->will($this->returnValue(FALSE));
		// make sure we try to cache the result
		$rule->expects($this->once())->method('updateCache');
		
		$state_data = new OLPBlackbox_CampaignStateData(array('campaign_name' => $campaign_name));
		$valid = $rule->isValid(
			new OLPBlackbox_Data(array('home_zip' => $home_zip)), 
			$state_data
		);
		$this->assertSame($expected_value, $valid);
		
		// make sure the store got inserted into the state data correctly
		if ($expected_value)
		{
			$this->assertSame($home_zip, intval($state_data->brick_and_mortar_store['zip_code']));
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
	 * Gets the data set for this test.
	 *
	 * @return PHPUnit_Extensions_Database_DataSet_XmlDataSet
	 */
	protected function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/BrickAndMortar.fixture.xml');
	}
}
?>
