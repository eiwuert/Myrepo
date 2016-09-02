<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test case for the MICR filter rule.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_Filter_MICRTest extends PHPUnit_Extensions_Database_TestCase
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
	 * Data provider for the testIsValid test.
	 *
	 * @return array
	 */
	public static function isValidDataProvider()
	{
		// TODO: Add more test cases... bare minimum here to see that things were working
		return array(
			array('123123123', array('12345'), '800551111', 1, 2, TRUE), // Matching ABA & Acct, different SSN, existing APP ID
			array('123123123', array('12345'), '800551111', 200, 2, FALSE), // Matching ABA & Acct, different SSN, new APP ID
			array('123123123', array('12345'), '800551111', 200, 3, TRUE), // Matching ABA & Acct, different SSN, new APP ID, different tier
			array('123123123', array('12345'), '800991111', 200, 2, TRUE), // Matching ABA, Acct, & SSN, new APP ID
			array('345345345', array('12345'), '800991111', 200, 2, TRUE), // New ABA, Matching Acct & SSN, new APP ID
			array('123123123', array('67890'), '800991111', 200, 2, TRUE), // Matching ABA & SSN, new Acct, new APP ID
		);
	}

	/**
	 * Tests the isValid walkthrough for direct deposit minimum recur.
	 *
	 * @param unknown_type $aba aba number to use for test
	 * @param unknown_type $accts account number array to use for test
	 * @param string $ssn SSN to use for test
	 * @param int $application_id the application ID to use for test
	 * @param unknown_type $tier the tier ID to use for the test
	 * @param unknown_type $expected the expected result of isValid
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValid($aba, $accts, $ssn, $application_id, $tier, $expected)
	{
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-30 days");

		$data = new OLPBlackbox_Data();
		$data->bank_aba_encrypted = $aba;
		$data->permutated_bank_account_encrypted = $accts;
		$data->social_security_number_encrypted = $ssn;
		$data->application_id = $application_id;

		$state_data = new OLPBlackbox_TargetCollectionStateData(array('tier_number' => $tier));

		$filter = $this->getMock(
			'OLPBlackbox_Rule_Filter_MICR',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitRuleEvent', 'hitRuleStat')
		);
		// Return our db instance
		$filter->expects($this->any())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Return our db name
		$filter->expects($this->any())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));
		$filter->expects($this->any())->method('getQueryDate')
			->will($this->returnValue($query_date));

		$valid = $filter->isValid($data, $state_data);

		$this->assertSame($expected, $valid);
	}

	/**
	 * Data provider for the testCanRun test.
	 *
	 * @return array
	 */
	public static function canRunDataProvider()
	{
		return array(
			array('123123123', array('12345'), '8005551111', 1, TRUE), // All set
			array(NULL, array('12345'), '8005551111', 1, FALSE), // Missing ABA
			array('123123123', array(), '8005551111', 1, FALSE), // No account numbers
			array('123123123', NULL, '8005551111', 1, FALSE), // NULL account numbers
			array('123123123', array('12345'), NULL, 1, FALSE), // Missing SSN
			array('123123123', array('12345'), '8005551111', NULL, TRUE), // Missing APP ID, should pass still
			array(NULL, array(), NULL, NULL, FALSE), // Missing everything
			array(NULL, NULL, NULL, NULL, FALSE), // Missing everything, NULL acct array
		);
	}

	/**
	 * Tests that can run fails correctly or passes correctly based on data values.
	 *
	 * @param unknown_type $aba aba number to use for test
	 * @param unknown_type $accts account number array to use for test
	 * @param string $ssn SSN to use for test
	 * @param int $application_id the application ID to use for test
	 * @param unknown_type $expected the expected result of isValid
	 * @dataProvider canRunDataProvider
	 * @return void
	 */
	public function testCanRun($aba, $accts, $ssn, $application_id, $expected)
	{
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-30 days");

		$data = new OLPBlackbox_Data();
		$data->bank_aba_encrypted = $aba;
		$data->permutated_bank_account_encrypted = $accts;
		$data->social_security_number_encrypted = $ssn;
		$data->application_id = $application_id;

		$filter = $this->getMock(
			'OLPBlackbox_Rule_Filter_MICR',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'runRule', 'hitEvent', 'hitStat')
		);
		// Return our specified date, expect our rule value
		$filter->expects($this->any())->method('getQueryDate')
			->will($this->returnValue($query_date));
		$filter->expects($this->any())->method('runRule')->will($this->returnValue(TRUE));

		$state_data = new Blackbox_StateData();
		$valid = $filter->isValid($data, $state_data);

		$this->assertEquals($expected, $valid);
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
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/MICR.fixture.xml');
	}
}
?>
