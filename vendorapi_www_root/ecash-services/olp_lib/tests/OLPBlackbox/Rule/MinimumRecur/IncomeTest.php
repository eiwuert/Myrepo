<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test case for the income recur rule.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_MinimumRecur_IncomeTest extends PHPUnit_Extensions_Database_TestCase
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
			array('800552222', 4, 5000, 10, 1, FALSE), // 3 changes within 10 days, current of $5000 montly
			array('800552222', 4, 4000, 10, 1, TRUE), // 3 changes within 10 days, current of $4000 montly
			array('800551111', 4, 5000, 10, 1, TRUE), // different SSN
			array('800552222', 4, 5000, 7, 1, TRUE), // shorter time period for the rule
			array('800552222', 0, 5000, 10, 1, TRUE), // number of changes is 0
			array('800553333', 4, 5000, 10, 2, FALSE), // 5 different incomes (greater than the 4 needed)
			array('800553333', 4, 5000, 10, 3, TRUE), // 5 different incomes (greater than the 4 needed) but with a different campaign/company
		);
	}
	
	/**
	 * Tests isValid for income recur.
	 * 
	 * The database fixture has all the created_date's back 10 days.
	 *
	 * @param string $ssn the SSN to use
	 * @param int $changes the number of different incomes
	 * @param int $income the income of the application
	 * @param int $rule_value the value of the rule (days)
	 * @param in $campaign_id the current campaign id on which this rule is running
	 * @param bool $expected_value the expected return from isValid
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValid($ssn, $changes, $income, $rule_value, $campaign_id, $expected_value)
	{
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-$rule_value days");

		$data = new OLPBlackbox_Data();
		$data->social_security_number_encrypted = $ssn;
		$data->income_monthly_net = $income;

		$state_data = new Blackbox_StateData();

		$rule = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_Income',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitRuleEvent', 'hitRuleStat', 'getCompanyCampaignIdsByCampaign')
		);
		// Return our db instance
		$rule->expects($this->any())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Return our db name
		$rule->expects($this->any())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));
		// Return our specified date, expect our rule value
		$rule->expects($this->any())->method('getQueryDate')
			->will($this->returnValue($query_date));
		// for this test just assume one campaign per company
		$rule->expects($this->any())->method('getCompanyCampaignIdsByCampaign')
			->will($this->returnValue(array($campaign_id)));
			
		$rule->setupRule(
			array(
				Blackbox_StandardRule::PARAM_VALUE => array('changes' => $changes, 'days' => $rule_value)
			)
		);

		$valid = $rule->isValid($data, $state_data);

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
			array('800991111', 3, 5000, 10, FALSE), // everything is set
			array(NULL, 3, 5000, 10, TRUE), // NULL SSN value
			array('', 3, 5000, 10, TRUE), // blank SSN value
			array('800991111', 3, NULL, 10, TRUE), // blank SSN value
		);
	}

	/**
	 * Tests that can run fails correctly or passes correctly based on data values.
	 *
	 * @param string $ssn the ssn of the customer to check
	 * @param int $changes the number of income changes allowed
	 * @param int $income the income of the application
	 * @param int $rule_value the days that we'll check
	 * @param bool $expected what we expect to get from the test
	 * @dataProvider canRunDataProvider
	 * @return void
	 */
	public function testCanRun($ssn, $changes, $income, $rule_value, $will_skip)
	{
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-$rule_value days");

		$data = new OLPBlackbox_Data();
		$data->social_security_number_encrypted = $ssn;
		$data->income_monthly_net = $income;

		$rule = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_Income',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'runRule', 'hitEvent', 'hitStat', 'onSkip'),
			array($changes)
		);
		// Return our specified date, expect our rule value
		$rule->expects($this->any())->method('getQueryDate')
			->will($this->returnValue($query_date));
			
		$rule->expects($this->exactly((int)$will_skip))->method('onSkip');

		$rule->setupRule(
			array(
				Blackbox_StandardRule::PARAM_VALUE => $rule_value
			)
		);

		$state_data = new Blackbox_StateData();
		$valid = $rule->isValid($data, $state_data);
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
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/Income.fixture.xml');
	}
}
?>
