<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test case for the pay date recur rule.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_MinimumRecur_PayDateTest extends PHPUnit_Extensions_Database_TestCase
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
			array('800552222', 3, '2008-02-25', 10, 1, FALSE), // 3 changes within 10 days, next pay date 2008-02-25
			array('800552222', 3, '2008-02-26', 10, 1, TRUE), // 3 changes within 10 days, next pay date 2008-02-26
			array('800552222', 4, '2008-02-25', 10, 1, TRUE), // 4 changes within 10 days, next pay date 2008-02-25
			array('800552222', 3, '2008-02-25', 5, 1, TRUE), // 3 changes within 5 days, next pay date 2008-02-25
			array('800552222', 3, '2008-02-25', 9, 1, TRUE), // 3 changes within 9 days, next pay date 2008-02-25
			array('800552222', 3, '2008-02-25', 10, 2, TRUE), // 3 changes within 10 days, next pay date 2008-02-25, and different company
			
			// More than the required number of changes
			array('800553333', 3, '2008-02-28', 10, 2, FALSE), // 3 changes within 10 days, next pay date 2008-02-28
			array('800553333', 3, '2008-02-25', 10, 3, TRUE), // matches one of the pay dates, has 2 different pay dates, and comes in on another company
			array('800553333', 3, '2008-02-28', 10, 3, TRUE), // 3 changes within 10 days, next pay date 2008-02-28, but a different company
		);
	}
	
	/**
	 * Tests isValid for pay date recur.
	 * 
	 * The database fixture has all the created_date's back 10 days.
	 *
	 * @param string $ssn the SSN to use
	 * @param int $changes the number of different pay dates
	 * @param string $pay_date the applicants next pay date
	 * @param int $rule_value the value of the rule (days)
	 * @param bool $expected_value the expected return from isValid
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValid($ssn, $changes, $pay_date, $rule_value, $campaign_id, $expected_value)
	{
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-$rule_value days");

		$data = new OLPBlackbox_Data();
		$data->social_security_number_encrypted = $ssn;
		$data->next_pay_date = $pay_date;

		$state_data = new Blackbox_StateData();

		$rule = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_PayDate',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitRuleEvent', 'hitRuleStat', 'getCompanyCampaignIdsByCampaign'),
			array($changes)
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
			array('800991111', 3, '2008-02-25', 10, TRUE), // everything is set
			array(NULL, 3, '2008-02-25', 10, FALSE), // NULL SSN value
			array('', 3, '2008-02-25', 10, FALSE), // blank SSN value
			array('800991111', 3, NULL, 10, FALSE), // no next pay date
			array('800991111', 3, '', 10, FALSE), // blank next pay date
		);
	}

	/**
	 * Tests that can run fails correctly or passes correctly based on data values.
	 *
	 * @param string $ssn the ssn of the customer to check
	 * @param int $changes the number of pay date changes allowed
	 * @param string $pay_date the next pay date of the applicant
	 * @param int $rule_value the days that we'll check
	 * @param bool $expected what we expect to get from the test
	 * @dataProvider canRunDataProvider
	 * @return void
	 */
	public function testCanRun($ssn, $changes, $pay_date, $rule_value, $expected)
	{
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-$rule_value days");

		$data = new OLPBlackbox_Data();
		$data->social_security_number_encrypted = $ssn;
		$data->next_pay_date = $pay_date;

		$rule = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_PayDate',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'runRule', 'hitEvent', 'hitStat', 'onSkip'),
			array($changes)
		);
		// Return our specified date, expect our rule value
		$rule->expects($this->any())->method('getQueryDate')
			->will($this->returnValue($query_date));
		$rule->expects($this->any())->method('runRule')->will($this->returnValue(TRUE));
		$rule->expects($this->exactly((int)!$expected))->method('onSkip')->will($this->returnValue(FALSE));
		
		$rule->setupRule(
			array(
				Blackbox_StandardRule::PARAM_VALUE => $rule_value
			)
		);

		$state_data = new Blackbox_StateData();
		$valid = $rule->isValid($data, $state_data);

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
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/PayDate.fixture.xml');
	}
}
?>
