<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test case for the email filter rule.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_Filter_CellPhoneTest extends PHPUnit_Extensions_Database_TestCase
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
			array('8885551111', 200, '12345', FALSE), // matching cell, matching license key
			array('8885552222', 200, '12345', TRUE), // matching license key, new cell
			array('8885551111', 200, '54321', TRUE), // matching cell, new license key
			array('8885552222', 200, '54321', TRUE), // new cell, new license key
			array('8885551111', 1, '12345', TRUE), // matching cell, matching license key, existing app ID
		);
	}

	/**
	 * Tests the isValid walkthrough for direct deposit minimum recur.
	 *
	 * @param string $cell_phone cell phone number to use
	 * @param int $application_id the application ID to use
	 * @param string $license_key the license key to use
	 * @param bool $expected_value the expected return of isValid
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValid($cell_phone, $application_id, $license_key, $expected_value)
	{
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-30 days");

		$data = new OLPBlackbox_Data();
		$data->phone_cell = $cell_phone;
		$data->application_id = $application_id;

		$state_data = new OLPBlackbox_TargetCollectionStateData();

		$filter = $this->getMock(
			'OLPBlackbox_Rule_Filter_CellPhone',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'getLicenseKey', 'hitRuleEvent', 'hitRuleStat')
		);
		// Return our db instance
		$filter->expects($this->any())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Return our db name
		$filter->expects($this->any())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));
		$filter->expects($this->any())->method('getQueryDate')
			->will($this->returnValue($query_date));
		$filter->expects($this->any())->method('getLicenseKey')
			->will($this->returnValue($license_key));

		$filter->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'phone_cell',
				Blackbox_StandardRule::PARAM_VALUE => 30 // totally bogus, isn't used
			)
		);

		$valid = $filter->isValid($data, $state_data);

		$this->assertSame($expected_value, $valid);
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
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/CellPhone.fixture.xml');
	}
}
?>
