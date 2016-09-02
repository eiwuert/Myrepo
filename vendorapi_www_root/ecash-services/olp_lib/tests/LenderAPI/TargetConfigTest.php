<?php

/**
 * Tests the LenderAPI configuration object which abstracts the target data stored
 * for a particular target, taking into account default inheritance and such.
 *
 * @group requires_blackbox
 * @todo remove from this group when issue #35145
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class LenderAPI_TargetConfigTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * Database connection.
	 *
	 * @var DB_Database_1
	 */
	protected $db;
	
	/**
	 * Test setup, must call parent setUp().
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->db = TEST_DB_CONNECTOR(TEST_BLACKBOX);
		parent::setUp();
	}
	
	/**
	 * Tests runtime overrides set on a target config.
	 * 
	 * Runtime overrides are arbitrary arrays which will resemble the LenderAPI
	 * configuration entries. This is mostly used in the LenderAPI Admin area to
	 * provide override URLs and post methods, etc.
	 *
	 * @dataProvider runtimeOverrideProvider
	 * @param array $runtime_override The fake config to override with.
	 * @param array $expected_config_items The configuration items we expect
	 * to be present in getConfig() (and how we expect them to look.)
	 * @param array $expected_constants The LenderAPI configuration (campaign)
	 * constants we expect to find in getConstants() (and how we expect them to look.)
	 * @return void
	 */
	public function testRuntimeOverride($runtime_override, $expected_config_items, $expected_constants)
	{
		$config_object = new LenderAPI_TargetConfig($this->db, 'OBB', 'TARGET');
		$config_object->setRuntimeOverride($runtime_override);
		
		$config = $config_object->getConfig();
		$constants = $config_object->getConstants();
		
		foreach ($expected_config_items as $key => $val)
		{
			$this->assertTrue(
				array_key_exists($key, $config), 
				"Config key $key wasn't even present in " . print_r($config, TRUE)
			);
			$this->assertEquals(
				$val, $config[$key], 'Configuration did not match expected value.'
			);
		}
		
		foreach ($expected_constants as $key => $val)
		{
			$this->assertTrue(
				array_key_exists($key, $constants), 
				"Constant key $key wasn't even present!"
			);
			$this->assertEquals(
				$val,
				$constants[$key],
				'Constant value did not meet expected value, looked in ' . print_r($constants, TRUE)
			);
		}
	}
	
	/**
	 * A quick test to make sure that target inheritance works properly.
	 *
	 * @return void
	 */
	public function testTargetInheritance()
	{
		$config_object = new LenderAPI_TargetConfig($this->db, 'OBB', 'CAMPAIGN');
		$config = $config_object->getConfig();
		$this->assertEquals(
			$config['vendor_api_url_LOCAL'], 
			'http://ezcashmedia.com', 
			'OBB campaign did not properly inherit from ezc'
		);
	}
	
	/**
	 * Test that Campaign Constants override properly based on 
	 *
	 * @dataProvider campaignConstantConfigProvider
	 * @param string $property_short The property short to pull target data from.
	 * @param array $expected_constants The expected campaign constants to get.
	 * @return void
	 */
	public function testCampaignConstantConfig($property_short, $expected_constants)
	{
		$config_object = new LenderAPI_TargetConfig($this->db, $property_short, 'CAMPAIGN');
		
		$found_constants = $config_object->getConstants();
		
		$this->assertEquals(array(), 
			array_diff($expected_constants, $found_constants),
			'Content of campaign constants did not match.'
		);
		$this->assertEquals(array(), 
			array_diff(array_keys($expected_constants), array_keys($found_constants)),
			'Keys for campaign constants did not match.'
		);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * Provides data to test campaign constants in TargetConfig.
	 *
	 * @return array
	 */
	public static function campaignConstantConfigProvider()
	{
		// NOTE: The expected values lower case the keys for the constants
		// since in process of posting, the keys will be made to be compliant with
		// XML tag name format.
		
		// OBB has 2 constants with DIFFERENT NAMES on it's target and campaign,
		// so every constant should be available from the config
		$separate_constants = array(
			'name1' => 'Value1', 'name2' => 'Value2', 'name3' => 'Value3', 'name4' => 'Value4',
		);
		
		// TGC has constants with the same name but different values, we want to 
		// make sure it ends up with only 2 entries.
		$overlapping_constants = array(
			'name1' => 'Campaign1', 'name2' => 'Campaign2',
		);
		return array(
			array('OBB', $separate_constants),
			array('TGC', $overlapping_constants),
		);
	}
	
	/**
	 * Tests using target_data like data to override what a target config has
	 * for it's values.
	 *
	 * @return void
	 */
	public static function runtimeOverrideProvider()
	{
		$runtime_override = array(
			'vendor_api_url_LOCAL' => 'http://nowhere.com',
			// for parsing reasons, the override must provide name AND value
			'vendor_api_constant_name_1' => 'Name1',
			'vendor_api_constant_value_1' => 'tricky', 
		);
		$expected_config_items = array(
			'vendor_api_url_LOCAL' => 'http://nowhere.com',
			'vendor_api_method_LOCAL' => 'POST_SOAP',
		);
		// constant keys end up XML normalized, hence lower case
		$expected_constants = array(
			'name1' => 'tricky',
			'name2' => 'Value2',
		);
		
		return array(
			array($runtime_override, $expected_config_items, $expected_constants),
		);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection 
	 * @see PHPUnit_Extensions_Database_TestCase::getConnection()
	 */
	protected function getConnection()
	{
		$connection = $this->createDefaultDBConnection(
			TEST_DB_PDO(TEST_BLACKBOX), 
			TEST_GET_DB_INFO(TEST_BLACKBOX)->name
		);
		return $connection;
	}
	
	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet 
	 * @see PHPUnit_Extensions_Database_TestCase::getDataSet()
	 */
	protected function getDataSet()
	{
		$dataset = $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/TargetConfig.xml');
		return $dataset;
	}
}

?>
