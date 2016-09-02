<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test case for the Email minimum recur rule.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_MinimumRecur_EmailTest extends PHPUnit_Extensions_Database_TestCase
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
			array('brandnew@test.com', 'CAC', 30, TRUE), // No SSN within 30 days
			array('test1@test.com', 'CAC', 30, FALSE), // 1 SSN within 30 days
			array('test1@test.com', 'CAC', 1, TRUE), // No SSN within 1 day
			array('test2@test.com', 'UFC', 30, TRUE), // SSN in DB, but in DISAGREED status
			array('test3@test.com', 'UFC', 30, TRUE), // SSN in DB, but in CONFIRMED_DISAGREED status
			array('test4@test.com', 'FWC', 30, FALSE), // SSN found with post in Blackbox_Post
			array('test4@test.com', 'FWC', 1, TRUE) // No SSN found within 1 day, with BB_Post data query
		);
	}

	/**
	 * Tests the isValid walkthrough for email minimum recur.
	 *
	 * @param string $email the email to use for the test
	 * @param string $campaign_name the campaign name to use for the test
	 * @param int $rule_value the rule value (days) to use for the test
	 * @param bool $expected_value the expecte value returned from isValid
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValid($email, $campaign_name, $rule_value, $expected_value)
	{
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-$rule_value days");

		$data = new OLPBlackbox_Data();
		$data->email_primary = $email;

		$state_data = new OLPBlackbox_CampaignStateData(array('campaign_name' => $campaign_name));

		$email_min_recur = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_Email',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitEvent', 'hitStat')
		);
		// Forces us to use out db connection
		$email_min_recur->expects($this->once())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Forces us to use our db name
		$email_min_recur->expects($this->once())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));
		// Forces the current date
		$email_min_recur->expects($this->once())->method('getQueryDate')
			->will($this->returnValue($query_date));

		$email_min_recur->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'email_primary',
				Blackbox_StandardRule::PARAM_VALUE => $rule_value
			)
		);

		$valid = $email_min_recur->isValid($data, $state_data);

		$this->assertEquals($expected_value, $valid);
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
