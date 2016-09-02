<?php

class ECash_HistoryProviderTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * @var ECash_HistoryProvider
	 */
	protected $_provider;

	protected $_lock;

	protected function setUp()
	{
		$this->_lock = getTestDatabase();
		$this->_lock->query("SELECT GET_LOCK('PHPUNIT', 120)");

		parent::setUp();

		$this->_provider = new ECash_HistoryProvider(
			getTestDatabase(),
			array('CA'),
			FALSE,
			FALSE
		);
	}

	protected function getSetUpOperation()
	{
		return new PHPUnit_Extensions_Database_Operation_Composite(array(
			new FastTruncate(),
		  new LongInsert(),
		));
	}

	protected function tearDown()
	{
		$this->_provider = NULL;
		parent::tearDown();

		$this->_lock->query("SELECT RELEASE_LOCK('PHPUNIT')");
		$this->_lock = NULL;
	}

	public function testIgnoredStatusesAreNotAddedToHistory()
	{
		$hist = new ECash_CustomerHistory();

		// took a known match from badMatchProvider
		$this->_provider->getHistoryBy(
			array('bank_aba' => '123123100', 'bank_account' => '123123123100', 'ssn' => '123451000'),
			$hist,
			array('bad')
		);

		$this->assertEquals(0, $hist->getCountBad());
	}

	public function testExcludedApplicationIsNotAddedToHistory()
	{
		$hist = new ECash_CustomerHistory();

		// exclude the ONE denied app
		$this->_provider->excludeApplication(200);

		$this->_provider->getHistoryBy(
			array('ssn' => '123452000'),
			$hist
		);

		$this->assertEquals(0, $hist->getCountDenied());
	}

	public function testDoNotLoanWithoutOverrideIsDNL()
	{
		$history = new ECash_CustomerHistory();
		$this->_provider->runDoNotLoan('123456000', $history);

		$this->assertTrue($history->getIsDoNotLoan('ca'));
	}

	public function testDoNotLoanRegulatoryFlagIsDNL()
	{
		$history = new ECash_CustomerHistory();
		$this->_provider->runDoNotLoan('123456002', $history);

		$this->assertTrue($history->getIsDoNotLoan('ca'));
	}

	public function testDoNotLoanWithCompanyOverrideIsNotDNL()
	{
		$history = new ECash_CustomerHistory();
		$this->_provider->runDoNotLoan('123456001', $history);

		$this->assertFalse($history->getIsDoNotLoan('ca'));
	}

	public function testDisagreed()
	{
		$history = new ECash_CustomerHistory();
		$this->_provider->getHistoryBy(
			array('ssn' => '123456000'),
			$history
		);

		$this->assertEquals(1, $history->getCountDisagreed());
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
		$this->_provider->getHistoryBy($match, $history);
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
		$this->_provider->getHistoryBy($match, $history);
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
		$this->_provider->getHistoryBy($match, $history);
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
		$this->_provider->getHistoryBy($match, $history);
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
		$history = $this->getMockHistory('pending', 13);
		$this->_provider->getHistoryBy($match, $history);
	}

	/**
	 * Tests that the purchase date of applications are passed
	 * @return NULL
	 */
	public function testPurchaseDateIsPassed()
	{
		$history = $this->getMock('ECash_CustomerHistory', array('addLoan'));
		$history->expects($this->atLeastOnce())
			->method('addLoan')
			->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), strtotime('2009-01-01 00:00:00'));

		$this->_provider->getHistoryBy(
			array('ssn' => '123456000'),
			$history
		);
	}

	/**
	 * Tests that only online confirmation applications are considered as having 'purchase' dates
	 * @return NULL
	 */
	public function testPurchaseDateSetOnOnlineConfirmationOnly()
	{
		$history = new ECash_CustomerHistory();

		$this->_provider->getHistoryBy(
			array('ssn' => '123457000'),
			$history
		);

		$this->assertEquals(1, $history->getPurchasedLeadCount('100 years'));
	}

	protected function getMockHistory($status, $count)
	{
		$history = $this->getMock(
			'ECash_CustomerHistory',
			array('addLoan')
		);

		// $company, $status, $app_id, $status_date = NULL
		$history->expects($this->exactly($count))
			->method('addLoan')
			->with('ca', $status, $this->anything(), $this->anything());

		return $history;
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
