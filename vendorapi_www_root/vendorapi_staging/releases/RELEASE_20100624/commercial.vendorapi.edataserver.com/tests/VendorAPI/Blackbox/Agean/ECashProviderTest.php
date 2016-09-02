<?php

class VendorAPI_Blackbox_Agean_ECashProviderTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * @var VendorAPI_Blackbox_Agean_ECashProvider
	 */
	protected $_provider;

	protected $_lock;

	protected function setUp()
	{
		$this->_lock = getTestDatabase();
		$this->_lock->query("SELECT GET_LOCK('PHPUNIT', 120)");

		parent::setUp();

		$this->_provider = new VendorAPI_Blackbox_Agean_ECashProvider(
			getTestDatabase(),
			array('jiffy'),
			FALSE,
			FALSE
		);
	}

	protected function tearDown()
	{
		$this->_provider = NULL;
		parent::tearDown();

		$this->_lock->query("SELECT RELEASE_LOCK('PHPUNIT')");
		$this->_lock = NULL;
	}

	public function testRecoveredApplicationIsNotAddedToHistory()
	{
		$history = $this->getMock('ECash_CustomerHistory', array('addLoan'));
		$history->expects($this->never())
			->method('addLoan');

		$this->_provider->getHistoryBy(array('ssn'=>'123451000'), $history);
	}

	/**
	 * Gets the database connection for this test.
	 *
	 * @return PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
	 */
	protected function getConnection()
	{
		return $this->createDefaultDBConnection(getTestPDODatabase(), $GLOBALS['db_name']);
	}

	/**
	 * Gets the data set for this test.
	 *
	 * @return PHPUnit_Extensions_Database_DataSet_XmlDataSet
	 */
	protected function getDataSet()
	{
		return $this->createFlatXMLDataSet(dirname(__FILE__).'/_fixtures/ECashProvider.fixture.xml');
	}
}

?>