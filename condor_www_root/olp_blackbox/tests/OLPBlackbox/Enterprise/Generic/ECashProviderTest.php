<?php

require_once 'OLPBlackboxTestSetup.php';
require_once 'libolution/DB/Database.1.php';

class OLPBlackbox_Enterprise_Generic_ECashProviderTest extends PHPUnit_Extensions_Database_TestCase
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
	 * Provides a set of information that provides bad loans only
	 *
	 * This covers all the current lookups...
	 *
	 * @return array
	 */
	public static function badMatchProvider()
	{
		return array(
			array(array('ssn' => '123451000')),
			array(array('bank_aba' => '123123100', 'bank_account' => '123123123100', 'ssn' => '123451000')),
			array(array('bank_aba' => '123123100', 'bank_account' => '123123123100', 'dob' => '1910-1-1')),
			array(array('email' => 'test100@test.com', 'dob' => '1910-1-1')),
			array(array('email' => 'test100@test.com', 'ssn' => '123451000')),
			array(array('phone_home' => '1234561000')),
			array(array('phone_home' => '1234561000', 'dob' => '1910-1-1')),
			array(array('legal_id_number' => 'NV1234100')),
			array(array('ssn' => '123451000')),
		);
	}

	/**
	 * Tests bad statuses
	 *
	 * @group previousCustomer
	 * @dataProvider badMatchProvider
	 * @param array $match
	 */
	public function testBad(array $match)
	{
		$history = $this->getMockHistory('bad', 17);

		$provider = $this->getProvider();
		$provider->getHistoryBy($match, $history);
	}

	/**
	 * Provides a set of information that provides denied loans only
	 *
	 * This covers all the current lookups...
	 *
	 * @return array
	 */
	public static function deniedMatchProvider()
	{
		return array(
			array(array('ssn' => '123452000')),
			array(array('bank_aba' => '123123200', 'bank_account' => '123123123200', 'ssn' => '123452000')),
			array(array('bank_aba' => '123123200', 'bank_account' => '123123123200', 'dob' => '1920-1-1')),
			array(array('email' => 'test200@test.com', 'dob' => '1920-1-1')),
			array(array('email' => 'test200@test.com', 'ssn' => '123452000')),
			array(array('phone_home' => '1234562000')),
			array(array('phone_home' => '1234562000', 'dob' => '1920-1-1')),
			array(array('legal_id_number' => 'NV1234200')),
			array(array('ssn' => '123452000')),
		);
	}


	/**
	 * Tests bad statuses
	 *
	 * @group previousCustomer
	 * @dataProvider deniedMatchProvider
	 * @param array $match
	 */
	public function testDenied(array $match)
	{
		$history = $this->getMockHistory('denied', 1);

		$provider = $this->getProvider();
		$provider->getHistoryBy($match, $history);
	}

	/**
	 * Provides a set of information that provides paid loans only
	 *
	 * This covers all the current lookups...
	 *
	 * @return array
	 */
	public static function paidMatchProvider()
	{
		return array(
			array(array('ssn' => '123453000')),
			array(array('bank_aba' => '123123300', 'bank_account' => '123123123300', 'ssn' => '123453000')),
			array(array('bank_aba' => '123123300', 'bank_account' => '123123123300', 'dob' => '1930-1-1')),
			array(array('email' => 'test300@test.com', 'dob' => '1930-1-1')),
			array(array('email' => 'test300@test.com', 'ssn' => '123453000')),
			array(array('phone_home' => '1234563000')),
			array(array('phone_home' => '1234563000', 'dob' => '1930-1-1')),
			array(array('legal_id_number' => 'NV1234300')),
			array(array('ssn' => '123453000')),
		);
	}


	/**
	 * Tests all paid statuses
	 *
	 * @group previousCustomer
	 * @dataProvider paidMatchProvider
	 * @param array $match
	 */
	public function testPaid(array $match)
	{
		$history = $this->getMockHistory('paid', 2);

		$provider = $this->getProvider();
		$provider->getHistoryBy($match, $history);
	}

	/**
	 * Provides a set of information that provides active loans only
	 *
	 * This covers all the current lookups...
	 *
	 * @return array
	 */
	public static function activeMatchProvider()
	{
		return array(
			array(array('ssn' => '123454000')),
			array(array('bank_aba' => '123123400', 'bank_account' => '123123123400', 'ssn' => '123454000')),
			array(array('bank_aba' => '123123400', 'bank_account' => '123123123400', 'dob' => '1940-1-1')),
			array(array('email' => 'test400@test.com', 'dob' => '1940-1-1')),
			array(array('email' => 'test400@test.com', 'ssn' => '123454000')),
			array(array('phone_home' => '1234564000')),
			array(array('phone_home' => '1234564000', 'dob' => '1940-1-1')),
			array(array('legal_id_number' => 'NV1234400')),
			array(array('ssn' => '123454000')),
		);
	}


	/**
	 * Tests all active statuses
	 *
	 * @group previousCustomer
	 * @dataProvider activeMatchProvider
	 * @param array $match
	 */
	public function testActive(array $match)
	{
		$history = $this->getMockHistory('active', 1);

		$provider = $this->getProvider();
		$provider->getHistoryBy($match, $history);
	}

	/**
	 * Provides a set of information that produces pending loans only
	 *
	 * This covers all the current lookups...
	 *
	 * @return array
	 */
	public static function pendingMatchProvider()
	{
		return array(
			array(array('ssn' => '123455000')),
			array(array('bank_aba' => '123123500', 'bank_account' => '123123123500', 'ssn' => '123455000')),
			array(array('bank_aba' => '123123500', 'bank_account' => '123123123500', 'dob' => '1950-1-1')),
			array(array('email' => 'test500@test.com', 'dob' => '1950-1-1')),
			array(array('email' => 'test500@test.com', 'ssn' => '123455000')),
			array(array('phone_home' => '1234565000')),
			array(array('phone_home' => '1234565000', 'dob' => '1950-1-1')),
			array(array('legal_id_number' => 'NV1234500')),
			array(array('ssn' => '123455000')),
		);
	}


	/**
	 * Tests all pending statuses
	 *
	 * @group previousCustomer
	 * @dataProvider pendingMatchProvider
	 * @param array $match
	 */
	public function testPending(array $match)
	{
		$history = $this->getMockHistory('pending', 11);

		$provider = $this->getProvider();
		$provider->getHistoryBy($match, $history);
	}

	protected function getMockHistory($status, $count)
	{
		$history = $this->getMock(
			'OLPBlackbox_Enterprise_CustomerHistory',
			array('addLoan')
		);

		// $company, $status, $app_id, $status_date = NULL
		$history->expects($this->exactly($count))
			->method('addLoan')
			->with('ca', $status, $this->anything(), $this->anything());

		return $history;
	}

	protected function getProvider()
	{
		$provider = $this->getMock(
			'OLPBlackbox_Enterprise_Generic_ECashProvider',
			array('getCompanyConnection'),
			array(array('CA'), FALSE, FALSE)
		);
		$provider->expects($this->any())
			->method('getCompanyConnection')
			->will($this->returnValue($this->get()));

		return $provider;
	}

	/**
	 * Gets the database connection for this test.
	 *
	 * @return PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
	 */
	protected function getConnection()
	{
		return $this->createDefaultDBConnection(TEST_DB_PDO_LDB(), TEST_GET_DB_INFO()->ldb_name);
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

	protected function get()
	{
		$db_info = TEST_GET_DB_INFO();

		return new DB_Database_1(
			"mysql:host={$db_info->host};port={$db_info->port};dbname={$db_info->ldb_name}",
			$db_info->user,
			$db_info->pass
		);
	}
}

?>
