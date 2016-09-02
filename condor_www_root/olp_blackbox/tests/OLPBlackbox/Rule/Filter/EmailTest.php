<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test case for the email filter rule.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_Filter_EmailTest extends PHPUnit_Extensions_Database_TestCase
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
			array('test1@test.com', '800991111', 1, 2, TRUE), // Matching app ID and email, no diff SSN's
			array('test1@test.com', '800991111', 200, 2, TRUE), // Matching email, no diff SSN's, new app ID
			array('test1@test.com', '800992222', 200, 2, FALSE), // Matching email, diff SSN, new app ID
			array('test2@test.com', '800992222', 200, 2, TRUE) // Matching email, diff SSN, new app ID, invalid status
		);
	}

	/**
	 * Tests the isValid walkthrough for direct deposit minimum recur.
	 *
	 * @param string $email email address to use
	 * @param string $ssn the ssn to use
	 * @param int $application_id the application ID to use
	 * @param int $tier_number the tier number to use
	 * @param bool $expected_value the expected return of isValid
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValid($email, $ssn, $application_id, $tier_number, $expected_value)
	{
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-30 days");

		$data = new OLPBlackbox_Data();
		$data->email_primary = $email;
		$data->social_security_number_encrypted = $ssn;
		$data->application_id = $application_id;

		$state_data = new OLPBlackbox_TargetCollectionStateData(array('tier_number' => $tier_number));

		$filter = $this->getMock(
			'OLPBlackbox_Rule_Filter_Email',
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

		$this->assertSame($expected_value, $valid);
	}

	/**
	 * Data provider for the testCanRun test.
	 *
	 * @return array
	 */
	public static function canRunDataProvider()
	{
		return array(
			array('test@test.com', '800991111', 1, TRUE), // All set
			array('test@test.com', '800991111', NULL, TRUE), // No app ID, should still pass
			array('test@test.com', NULL, 1, FALSE), // No SSN
			array(NULL, '800991111', 1, FALSE) // No Email
		);
	}

	/**
	 * Tests that can run fails correctly or passes correctly based on data values.
	 *
	 * @param string $email the email we're using for the test
	 * @param string $ssn the SSN we're using for the test
	 * @param bool $application_id application ID we're using
	 * @param bool $expected the expected value we'll get from isValid
	 * @dataProvider canRunDataProvider
	 * @return void
	 */
	public function testCanRun($email, $ssn, $application_id, $expected)
	{
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-30 days");

		$data = new OLPBlackbox_Data();
		$data->email_primary = $email;
		$data->social_security_number_encrypted = $ssn;
		$data->application_id = $application_id;

		$filter = $this->getMock(
			'OLPBlackbox_Rule_Filter_Email',
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
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/Email.fixture.xml');
	}
}
?>
