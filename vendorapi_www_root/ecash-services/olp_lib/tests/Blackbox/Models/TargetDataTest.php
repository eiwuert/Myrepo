<?php

/**
 * Test the TargetData models.
 * 
 * @package Blackbox
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @group requires_blackbox
 */
class Blackbox_Models_TargetDataTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * Tests the method which loads target data objects based on property shorts.
	 *
	 * @dataProvider loadAllByPropertyShortsProvider
	 * @param array $property_shorts The property shorts to use in the method call.
	 * @param array $expected_data The data, indexed by target_data_type_id and
	 * containing a data_value from target data, that we expect to be returned.
	 * @return void
	 */
	public function testLoadAllByPropertyShorts(array $property_shorts, array $target_data_types, array $expected_data)
	{
		$target_data = new Blackbox_Models_TargetData(TEST_DB_CONNECTOR(TEST_BLACKBOX));
		
		$resulting_data = array();
		foreach ($target_data->loadAllByPropertyShorts($property_shorts, $target_data_types) as $target_data)
		{
			$resulting_data[] = $target_data->data_value;
		}
		
		$this->assertEquals($expected_data, $resulting_data, "Target data pulled was incorrect.");
	}
	
	/**
	 * Provides data to {@see Blackbox_Models_TargetDataTest::testLoadAllByPropertyShorts()}
	 *
	 * @return array
	 */
	public static function loadAllByPropertyShortsProvider()
	{
		return array(
			array(array('cav'), array(), array('tequila!', 'free at last!', 'attack!')),
			array(array('cav'), array(1), array('tequila!', 'free at last!')),
			array(array('doa'), array(), array()),
		);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection 
	 * @see PHPUnit_Extensions_Database_TestCase::getConnection()
	 */
	protected function getConnection()
	{
		return $this->createDefaultDBConnection(
			TEST_DB_PDO(TEST_BLACKBOX), 
			TEST_GET_DB_INFO(TEST_BLACKBOX)->name
		);
	}
	
	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet 
	 * @see PHPUnit_Extensions_Database_TestCase::getDataSet()
	 */
	protected function getDataSet()
	{
		return $this->createXMLDataSet(
			dirname(__FILE__) . '/_fixtures/TargetData.xml'
		);
	}
}

?>