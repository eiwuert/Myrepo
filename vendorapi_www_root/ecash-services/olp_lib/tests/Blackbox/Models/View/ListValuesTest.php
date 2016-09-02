<?php
/**
 * Test case for the Blackbox_Models_View_ListValues model.
 *
 * @group blackbox_models
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Models_View_ListValuesTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * Tests the getValues function.
	 *
	 * @return void
	 */
	public function testGetValues()
	{
		$model = new Blackbox_Models_View_ListValues(TEST_DB_CONNECTOR(TEST_BLACKBOX));
		$list_values = $model->getValues(1);
		
		$list_values->rewind();
		$value = $list_values->current();
		$this->assertEquals(1, $value->value_id);
		$this->assertEquals('test_value1', $value->value);
		
		$value = $list_values->next();
		$this->assertEquals(2, $value->value_id);
		$this->assertEquals('test_value2', $value->value);
	}
	
	/**
	 * Tests the getAllByValues function.
	 *
	 * @return void
	 */
	public function testGetAllByValues()
	{
		$model = new Blackbox_Models_View_ListValues(TEST_DB_CONNECTOR(TEST_BLACKBOX));
		$list_values = $model->getAllByValues(array('test_value1', 'test_value2'));
		
		$list_values->rewind();
		$value = $list_values->current();
		$this->assertEquals(1, $value->value_id);
		$this->assertEquals('test_value1', $value->value);
		
		$value = $list_values->next();
		$this->assertEquals(2, $value->value_id);
		$this->assertEquals('test_value2', $value->value);
	}
	
	/**
	 * Gets the database connection for this test.
	 *
	 * @return PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
	 */
	protected function getConnection()
	{
		return $this->createDefaultDBConnection(TEST_DB_PDO(TEST_BLACKBOX), TEST_GET_DB_INFO(TEST_BLACKBOX)->name);
	}

	/**
	 * Gets the data set for this test.
	 *
	 * @return PHPUnit_Extensions_Database_DataSet_XmlDataSet
	 */
	protected function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/ListValues.xml');
	}
}
