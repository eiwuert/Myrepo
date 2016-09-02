<?php

/**
 * Testing ECash_Models_DocumentHash
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECash_Models_DocumentHashTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * @var ECash_Models_ApplicationVersion
	 */
	private $model;

	/**
	 * Sets up the test object
	 */
	protected function setUp()
	{
		parent::setUp();

		$db = getTestDatabase();
		$db->connect();
		$this->model = new ECash_Models_DocumentHash($db);
	}

	/**
	 * Retrieves the default database connection
	 *
	 * @return DB_IConnection_1
	 */
	protected function getConnection()
	{
		return $this->createDefaultDBConnection(getTestPDODatabase(), $GLOBALS['db_name']);
	}

	/**
	 * Retrieves the XML Dataset from the file system
	 * 
	 * @return XML_Dataset
	 */
	protected function getDataSet()
	{
		$dataset = $this->createFlatXMLDataSet(dirname(__FILE__) . '/_fixtures/DocumentHashTest.xml');
		return new PHPUnit_Extensions_Database_DataSet_ReplacementDataSet(
			$dataset,
			array('[[NOW]]' => date('Y-m-d H:i:s', time()), '[[60DAYS]]' => date('Y-m-d H:i:s', strtotime('-61 days')))
		);
	}

	public function testRemoveEntriesBefore()
	{
		$this->model->removeEntriesBefore(strtotime('-60 days'), 1);

		$expected_dataset = new PHPUnit_Extensions_Database_DataSet_ReplacementDataSet(
			$this->createFlatXMLDataSet(dirname(__FILE__) . '/_expected/DocumentHash_RemoveEntriesBefore.xml'),
			array('[[NOW]]' => date('Y-m-d H:i:s', time()), '[[60DAYS]]' => date('Y-m-d H:i:s', strtotime('-61 days')))
		);

		$this->assertDataSetsEqual(
			$expected_dataset, 
			new PHPUnit_Extensions_Database_DataSet_DataSetFilter(
				$this->getConnection()->createDataSet(array('document_hash')),
				array('document_hash' => array('date_modified', 'date_created'))
			)
		);
	}

	public function testRemoveEntriesBeforeAllCompanies()
	{
		$this->model->removeEntriesBefore(strtotime('-60 days'));

		$expected_dataset = new PHPUnit_Extensions_Database_DataSet_ReplacementDataSet(
			$this->createFlatXMLDataSet(dirname(__FILE__) . '/_expected/DocumentHash_RemoveEntriesBeforeAllCompanies.xml'),
			array('[[NOW]]' => date('Y-m-d H:i:s', time()), '[[60DAYS]]' => date('Y-m-d H:i:s', strtotime('-61 days')))
		);

		$this->assertDataSetsEqual(
			$expected_dataset, 
			new PHPUnit_Extensions_Database_DataSet_DataSetFilter(
				$this->getConnection()->createDataSet(array('document_hash')),
				array('document_hash' => array('date_modified', 'date_created'))
			)
		);
	}

}

?>
