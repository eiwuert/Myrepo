<?php

/**
 * Test the TargetData view.
 *
 * @group blackbox_models
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class Blackbox_Models_View_TargetDataTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * Test the method of Blackbox_Models_View_TargetData which gets targetdata
	 * based on property short.
	 * @return void
	 */
	public function testGetDataByPropertyShort()
	{
		$view = new Blackbox_Models_View_TargetData(
			TEST_DB_CONNECTOR(TEST_BLACKBOX)
		);
		
		$views = $view->getDataByPropertyShort('ex1');
		
		foreach ($views as $view) {
			$this->assertEquals($view->data_value, 'http://nowhere.com');
			$this->assertEquals($view->data_name, 'vendor_api_url_LOCAL');
		}
	}
	/**
	 * 
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection 
	 * @see PHPUnit_Extensions_Database_TestCase::getConnection()
	 */
	protected function getConnection ()
	{
		return $this->createDefaultDBConnection(
			TEST_DB_PDO(TEST_BLACKBOX), 
			TEST_GET_DB_INFO(TEST_BLACKBOX)->name
		);
	}
	/**
	 * 
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet 
	 * @see PHPUnit_Extensions_Database_TestCase::getDataSet()
	 */
	protected function getDataSet ()
	{
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/TargetData.xml');
	}
}
?>
