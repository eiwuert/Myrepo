<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test case for the direct deposit minimum recur rule.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_MinimumRecur_DirectDepositTest extends PHPUnit_Extensions_Database_TestCase
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
			array('800991111', TRUE, 30, TRUE), // No SSN within 30 days
			array('800992222', TRUE, 30, FALSE), // 1 SSN within 30 days
			array('800992222', 'TRUE', 30, FALSE), // 1 SSN within 30 days
			array('800992222', TRUE, 1, TRUE), // No SSN within 1 day
			array('800992222', FALSE, 1, TRUE), // Doesn't have direct deposit
			array('800992222', 'FALSE', 1, TRUE), // Doesn't have direct deposit
		);
	}

	/**
	 * Tests the isValid walkthrough for direct deposit minimum recur.
	 *
	 * @param string $ssn the SSN to use
	 * @param bool|string $direct_deposit the value of direct deposit
	 * @param int $rule_value the value of the rule (days)
	 * @param bool $expected_value the expected return from isValid
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValid($ssn, $direct_deposit, $rule_value, $expected_value)
	{
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-$rule_value days");

		$data = new OLPBlackbox_Data();
		$data->social_security_number = $ssn;
		$data->income_direct_deposit = $direct_deposit;

		$state_data = new Blackbox_StateData();

		$dd_recur = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_DirectDeposit',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitRuleEvent', 'hitRuleStat')
		);
		// Return our db instance
		$dd_recur->expects($this->any())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Return our db name
		$dd_recur->expects($this->any())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));
		// Return our specified date, expect our rule value
		$dd_recur->expects($this->any())->method('getQueryDate')->with($rule_value)
			->will($this->returnValue($query_date));

		$dd_recur->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'social_security_number',
				Blackbox_StandardRule::PARAM_VALUE => $rule_value
			)
		);

		$valid = $dd_recur->isValid($data, $state_data);

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
			array('800991111', 30, FALSE, TRUE), // Direct deposit set, SSN set
			array('800991111', 30, NULL, FALSE), // Direct deposit not set
			array(NULL, 30, FALSE, FALSE), // SSN not set
			array(NULL, 30, NULL, FALSE) // SSN & direct deposit not set
		);
	}

	/**
	 * Tests that can run fails correctly or passes correctly based on data values.
	 *
	 * @param string $ssn the ssn of the customer to check
	 * @param int $rule_value the days that we'll check
	 * @param mixed $direct_deposit whether we have the direct deposit value or it's value
	 * @param bool $expected what we expect to get from the test
	 * @dataProvider canRunDataProvider
	 * @return void
	 */
	public function testCanRun($ssn, $rule_value, $direct_deposit, $expected)
	{
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-$rule_value days");

		$data = new OLPBlackbox_Data();
		$data->social_security_number = $ssn;
		$data->income_direct_deposit = $direct_deposit;

		$dd_recur = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_DirectDeposit',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'runRule', 'hitEvent', 'hitStat', 'onSkip')
		);
		// Return our specified date, expect our rule value
		$dd_recur->expects($this->any())->method('getQueryDate')
			->will($this->returnValue($query_date));
		$dd_recur->expects($this->any())->method('runRule')->will($this->returnValue(TRUE));
		$dd_recur->expects($this->exactly((int)!$expected))->method('onSkip')->will($this->returnValue(FALSE));
		
		$dd_recur->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'social_security_number',
				Blackbox_StandardRule::PARAM_VALUE => $rule_value
			)
		);

		$state_data = new Blackbox_StateData();
		$valid = $dd_recur->isValid($data, $state_data);

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
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/DirectDeposit.fixture.xml');
	}
}
?>
