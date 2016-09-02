<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test case for the SSN minimum recur rule.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_MinimumRecur_SSNTest extends PHPUnit_Extensions_Database_TestCase
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
		return array(
			array('800551111', 'CAC', 30, TRUE), // No SSN within 30 days
			array('800552222', 'CAC', 30, FALSE), // 1 SSN within 30 days
			array('800552222', 'CAC', 1, TRUE), // No SSN within 1 day
			array('800553333', 'UFC', 30, TRUE), // SSN in DB, but in DISAGREED status
			array('800554444', 'UFC', 30, TRUE), // SSN in DB, but in CONFIRMED_DISAGREED status
			array('800555555', 'FWC', 30, FALSE), // SSN found with post in Blackbox_Post
			array('800555555', 'FWC', 1, TRUE) // No SSN found within 1 day, with BB_Post data query
		);
	}

	/**
	 * Tests the isValid walkthrough for email minimum recur.
	 *
	 * @param string $ssn the ssn to use for the test
	 * @param string $campaign_name the campaign name to use for the test
	 * @param int $rule_value the rule value (days) to use for the test
	 * @param bool $expected_value the expecte value returned from isValid
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValid($ssn, $campaign_name, $rule_value, $expected_value)
	{
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-$rule_value days");

		$data = new OLPBlackbox_Data();
		$data->social_security_number = $ssn;

		$state_data = new OLPBlackbox_CampaignStateData(array('campaign_name' => $campaign_name));

		$email_min_recur = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_SSN',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitRuleEvent', 'hitRuleStat')
		);
		// Return our db instance
		$email_min_recur->expects($this->any())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Return our db name
		$email_min_recur->expects($this->any())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));
		// Return our specified date, expect our rule value
		$email_min_recur->expects($this->any())->method('getQueryDate')->with($rule_value)
			->will($this->returnValue($query_date));

		$email_min_recur->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'social_security_number',
				Blackbox_StandardRule::PARAM_VALUE => $rule_value
			)
		);

		$valid = $email_min_recur->isValid($data, $state_data);

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
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/SSN.fixture.xml');
	}
}
?>
