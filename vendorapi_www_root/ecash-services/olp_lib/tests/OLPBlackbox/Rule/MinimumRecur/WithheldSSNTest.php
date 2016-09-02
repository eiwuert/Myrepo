<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test case for the SSN minimum recur rule.
 *
 * @author Rob Voss <rob.voss@sellingsource.com>
 */
class OLPBlackbox_Rule_MinimumRecur_WithheldSSNTest extends PHPUnit_Extensions_Database_TestCase
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
			array('123456789', 'CM1', 30, FALSE), // 1 SSN within 30 days
			array('123456789', 'CM1', 29, TRUE), // No SSN within 29 days
			array('923456781', 'CAC', 30, FALSE), // 1 SSN within 30 days
			array('987654321', 'CM1', 30, FALSE, array('CM1', 'CM1_A')), // 1 SSN within 30 days on aliased CM1_A target
		);
	}

	/**
	 * Tests the isValid walkthrough for SSN minimum recur.
	 *
	 * @param string $ssn the ssn to use for the test
	 * @param string $campaign_name the campaign name to use for the test
	 * @param int $rule_value the rule value (days) to use for the test
	 * @param bool $expected_value the expected value returned from isValid
	 * @param bool $properties optional array of properties for mocking getProperties
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValid($ssn, $campaign_name, $rule_value, $expected_value, $properties='')
	{
		$this->markTestIncomplete("database queries don't work with new schemas");
		
		$current_time = '2008-08-19 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-$rule_value days");

		$data = new OLPBlackbox_Data();
		$data->social_security_number = $ssn;

		$state_data = new OLPBlackbox_CampaignStateData(array('campaign_name' => $campaign_name));

		$withheldssn_min_recur = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_WithheldSSN',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitRuleEvent', 'hitRuleStat', 'getProperties')
		);
		// Return our db instance
		$withheldssn_min_recur->expects($this->any())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Return our db name
		$withheldssn_min_recur->expects($this->any())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));
		// Return our specified date, expect our rule value
		$withheldssn_min_recur->expects($this->any())->method('getQueryDate')->with($rule_value)
			->will($this->returnValue($query_date));
		// Return the expected aliases
		if (!$properties) $properties = array($campaign_name);
		$withheldssn_min_recur->expects($this->any())->method('getProperties')->will($this->returnValue($properties));
		
		$withheldssn_min_recur->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'social_security_number',
				Blackbox_StandardRule::PARAM_VALUE => $rule_value
			)
		);

		$valid = $withheldssn_min_recur->isValid($data, $state_data);

		$this->assertSame($expected_value, $valid);
		Cache_Memcache::getInstance()->delete($withheldssn_min_recur->getCacheKey());
	}
	
	/**
	 * Tests memcache to see that the value for the check was stored for a failed
	 * application.
	 *
	 * @return void
	 */
	public function testMemcacheFailedCheck()
	{
		$this->markTestSkipped("we probably shouldn't be testing memcache");
		
		$current_time = '2008-08-19 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-30 days");

		$data = new OLPBlackbox_Data();
		$data->social_security_number = '923456781';

		$state_data = new OLPBlackbox_CampaignStateData(array('campaign_name' => 'CAC'));

		$withheldssn_min_recur = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_WithheldSSN',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitEvent', 'hitStat')
		);
		// Forces us to use out db connection
		$withheldssn_min_recur->expects($this->once())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Forces us to use our db name
		$withheldssn_min_recur->expects($this->once())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));
		// Forces the current date
		$withheldssn_min_recur->expects($this->any())->method('getQueryDate')
			->will($this->returnValue($query_date));

		$withheldssn_min_recur->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'social_security_number',
				Blackbox_StandardRule::PARAM_VALUE => 30
			)
		);

		$valid = $withheldssn_min_recur->isValid($data, $state_data);
		
		// 1 is the value that will be set in memcache
		$this->assertEquals(1, Cache_Memcache::getInstance()->get($withheldssn_min_recur->getCacheKey()));
		Cache_Memcache::getInstance()->delete($withheldssn_min_recur->getCacheKey());
	}
	
	/**
	 * Tests memcache to see that the value for the check is not stored for a passed
	 * application.
	 *
	 * @return void
	 */
	public function testMemcachePassCheck()
	{
		$this->markTestIncomplete("database queries don't work with new schemas");
		$current_time = '2008-08-19 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-30 days");

		$data = new OLPBlackbox_Data();
		$data->social_security_number = '123456789';

		$state_data = new OLPBlackbox_CampaignStateData(array('campaign_name' => 'CAC'));

		$withheldssn_min_recur = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_WithheldSSN',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitEvent', 'hitStat')
		);
		// Forces us to use out db connection
		$withheldssn_min_recur->expects($this->once())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Forces us to use our db name
		$withheldssn_min_recur->expects($this->once())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));
		// Forces the current date
		$withheldssn_min_recur->expects($this->any())->method('getQueryDate')
			->will($this->returnValue($query_date));

		$withheldssn_min_recur->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'social_security_number',
				Blackbox_StandardRule::PARAM_VALUE => 30
			)
		);

		$valid = $withheldssn_min_recur->isValid($data, $state_data);
		
		// Memcache won't find the key
		$this->assertFalse(Cache_Memcache::getInstance()->get($withheldssn_min_recur->getCacheKey()));
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
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/Withheld.SSN.fixture.xml');
	}
}
?>
