<?php

/**
 * Base test class for factories, does the static setup in the db needed.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 * @subpackage Factory
 */
abstract class OLPBlackbox_Factory_Base extends PHPUnit_Extensions_Database_TestCase 
{
	
	/**
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection 
	 * @see PHPUnit_Extensions_Database_TestCase::getConnection()
	 */
	protected function getConnection() 
	{
		return $this->createDefaultDBConnection(
			TEST_DB_PDO_BBXADMIN(), TEST_GET_DB_INFO(TEST_BLACKBOX)->name
		);
	}
	
	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet 
	 * @see PHPUnit_Extensions_Database_TestCase::getDataSet()
	 */
	final protected function getDataSet() 
	{
		$base_data_set = $this->createXMLDataSet(
			dirname(__FILE__).'/_fixtures/BaseTest.xml'
		);
		
		return new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(
			array($base_data_set, $this->getFactoryDataSet())
		);
	}	

	/**
	 * Returns a bbxadmin database connection.
	 * @return DB_Database_1
	 */
	protected function getFactoryConnection()
	{
		$info = TEST_GET_DB_INFO(TEST_BLACKBOX);
		$dsn = "mysql:dbname={$info->name};host={$info->host}";
		if (!empty($info->port))
		{
			$dsn .= ";port={$info->port}";
		}
		$db = new DB_Database_1($dsn, $info->user, $info->pass);
		
		return $db;
	}

	/**
	 * Returns the children's data set which can contain just target/rule entries.
	 * @return PHPUnit_Extensions_Database_DataSet_AbstractDataSet
	 */
	abstract protected function getFactoryDataSet();
}

?>
