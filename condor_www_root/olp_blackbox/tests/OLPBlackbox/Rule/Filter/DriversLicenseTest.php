<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test case for the email filter rule.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_Filter_DriversLicenseTest extends PHPUnit_Extensions_Database_TestCase
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
			array('11111', '800991111', 1, 'CA', 2, TRUE), // Matching app ID and email, no diff SSN's
			array('11111', '800991111', 200, 'CA', 2, TRUE), // Matching DL #, no diff SSN's, new app ID
			array('11111', '800992222', 200, 'CA', 2, FALSE), // Matching DL #, diff SSN, new app ID
			array('11111', '800992222', 200, 'HI', 2, TRUE), // Matching DL #, diff SSN, diff state, new app ID
			array('22222', '800992222', 200, 'CA', 2, TRUE), // Matching DL #, diff SSN, new app ID, invalid status
			array('NONE', '800991111', 200, 'CA', 2, TRUE), // 'NONE' DL #
			array('NA', '800991111', 200, 'CA', 2, TRUE), // 'NA' DL #
			array('N/A', '800991111', 200, 'CA', 2, TRUE), // 'N/A' DL #
		);
	}

	/**
	 * Tests the isValid walkthrough for direct deposit minimum recur.
	 *
	 * @param string $dl_number drivers license to use
	 * @param string $ssn the ssn to use
	 * @param int $application_id the application ID to use
	 * @param string $state the state of the license
	 * @param int $tier_number the tier number to use
	 * @param bool $expected_value the expected return of isValid
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValid($dl_number, $ssn, $application_id, $state, $tier_number, $expected_value)
	{
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-30 days");

		$data = new OLPBlackbox_Data();
		$data->state_id_number = $dl_number;
		$data->state_issued_id = $state;
		$data->social_security_number_encrypted = $ssn;
		$data->application_id = $application_id;

		$state_data = new OLPBlackbox_TargetCollectionStateData(array('tier_number' => $tier_number));

		$filter = $this->getMock(
			'OLPBlackbox_Rule_Filter_DriversLicense',
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
			array('111111', '800991111', 1, TRUE), // All set
			array('111111', '800991111', NULL, TRUE), // No app ID, should still pass
			array('111111', NULL, 1, FALSE), // No SSN
			array(NULL, '800991111', 1, FALSE) // No Email
		);
	}

	/**
	 * Tests that can run fails correctly or passes correctly based on data values.
	 *
	 * @param string $dl_number the drivers licnese we're using for the test
	 * @param string $ssn the SSN we're using for the test
	 * @param bool $application_id application ID we're using
	 * @param bool $expected the expected value we'll get from isValid
	 * @dataProvider canRunDataProvider
	 * @return void
	 */
	public function testCanRun($dl_number, $ssn, $application_id, $expected)
	{
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-30 days");

		$data = new OLPBlackbox_Data();
		$data->state_id_number = $dl_number;
		$data->social_security_number_encrypted = $ssn;
		$data->application_id = $application_id;

		$filter = $this->getMock(
			'OLPBlackbox_Rule_Filter_DriversLicense',
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
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/DriversLicense.fixture.xml');
	}
}
?>
