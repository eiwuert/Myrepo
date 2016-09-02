<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test case for the SSN minimum recur rule.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 * @author Adam Englander <adam.englander@sellingsource.com>
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
			array('800555555', 'FWC', 1, TRUE), // No SSN found within 1 day, with BB_Post data query
			array('800556666', 'NSC_ST1', 30, FALSE, array('NSC', 'NSC_ST1')), // 1 SSN within 30 days on aliased target
		);
	}

	/**
	 * Tests the isValid walkthrough for SSN minimum recur.
	 *
	 * @param string $ssn the ssn to use for the test
	 * @param string $campaign_name the campaign name to use for the test
	 * @param int $rule_value the rule value (days) to use for the test
	 * @param bool $expected_value the expecte value returned from isValid
	 * @param bool $properties optional array of properties for mocking getProperties
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValid($ssn, $campaign_name, $rule_value, $expected_value, $properties='')
	{
		$this->markTestIncomplete("database queries don't work with new schemas");
		
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-$rule_value days");

		$data = new OLPBlackbox_Data();
		$data->social_security_number = $ssn;

		$state_data = new OLPBlackbox_CampaignStateData(array('campaign_name' => $campaign_name));

		$ssn_min_recur = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_SSN',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitRuleEvent', 'hitRuleStat', 'checkCache', 'updateCache', 'getProperties', 'onSkip')
		);
		// Return our db instance
		$ssn_min_recur->expects($this->any())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Return our db name
		$ssn_min_recur->expects($this->any())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));
		// Return our specified date, expect our rule value
		$ssn_min_recur->expects($this->any())->method('getQueryDate')->with($rule_value)
			->will($this->returnValue($query_date));
		// We assume the cache doesn't have a value yet
		$ssn_min_recur->expects($this->any())->method('checkCache')->will($this->returnValue(0));
		// Return the expected aliases
		if (!$properties) $properties = array($campaign_name);
		$ssn_min_recur->expects($this->any())->method('getProperties')->will($this->returnValue($properties));
		$ssn_min_recur->expects($this->never())->method('onSkip');
		
		$ssn_min_recur->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'social_security_number',
				Blackbox_StandardRule::PARAM_VALUE => $rule_value
			)
		);

		$valid = $ssn_min_recur->isValid($data, $state_data);

		$this->assertSame($expected_value, $valid);
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
		
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-30 days");

		$data = new OLPBlackbox_Data();
		$data->social_security_number = '800552222';

		$state_data = new OLPBlackbox_CampaignStateData(array('campaign_name' => 'CAC'));

		$ssn_min_recur = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_SSN',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitEvent', 'hitStat', 'onSkip')
		);
		// Forces us to use out db connection
		$ssn_min_recur->expects($this->once())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Forces us to use our db name
		$ssn_min_recur->expects($this->once())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));
		// Forces the current date
		$ssn_min_recur->expects($this->any())->method('getQueryDate')
			->will($this->returnValue($query_date));
		$ssn_min_recur->expects($this->never())->method('onSkip');
		
		$ssn_min_recur->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'social_security_number',
				Blackbox_StandardRule::PARAM_VALUE => 30
			)
		);

		$valid = $ssn_min_recur->isValid($data, $state_data);
		
		// 1 is the value that will be set in memcache
		$this->assertEquals(1, Cache_Memcache::getInstance()->get($ssn_min_recur->getCacheKey()));
		Cache_Memcache::getInstance()->delete($ssn_min_recur->getCacheKey());
	}
	
	/**
	 * Tests memcache to see that the value for the check is not stored for a passed
	 * application.
	 *
	 * @return void
	 */
	public function testMemcachePassCheck()
	{
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-30 days");

		$data = new OLPBlackbox_Data();
		$data->social_security_number = '800551111';

		$state_data = new OLPBlackbox_CampaignStateData(array('campaign_name' => 'CAC'));

		$ssn_min_recur = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_SSN',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitEvent', 'hitStat', 'onSkip')
		);
		// Forces us to use out db connection
		$ssn_min_recur->expects($this->once())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Forces us to use our db name
		$ssn_min_recur->expects($this->once())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));
		// Forces the current date
		$ssn_min_recur->expects($this->any())->method('getQueryDate')
			->will($this->returnValue($query_date));

		$ssn_min_recur->expects($this->never())->method('onSkip');
		$ssn_min_recur->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'social_security_number',
				Blackbox_StandardRule::PARAM_VALUE => 30
			)
		);

		$valid = $ssn_min_recur->isValid($data, $state_data);
		
		// Memcache won't find the key
		$this->assertFalse(Cache_Memcache::getInstance()->get($ssn_min_recur->getCacheKey()));
	}
	
	/**
	 * Tests that when it's an Enterprise company it doesn't run the blackbox_post query.
	 *
	 * @return void
	 */
	public function testEnterpriseCampaignsDoNotRunBlackboxPostQuery()
	{
		$current_time = '2008-02-24 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-30 days");
		
		$data = new OLPBlackbox_Data();
		$data->social_security_number = '800552222';
		$state_data = new OLPBlackbox_CampaignStateData(array('campaign_name' => 'd1'));
		
		$ssn_min_recur = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_SSN',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitEvent', 'hitStat', 'onSkip')
		);
		
		$ssn_min_recur->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'social_security_number',
				Blackbox_StandardRule::PARAM_VALUE => 30
			)
		);
		
		$db = $this->getMock('MySQL_4');
		$db->expects($this->once())->method('Query');
		
		$ssn_min_recur->expects($this->once())->method('getDbInstance')
			->will($this->returnValue($db));
		$ssn_min_recur->expects($this->any())->method('getQueryDate')
			->will($this->returnValue($query_date));
		$ssn_min_recur->expects($this->never())->method('onSkip');
		$ssn_min_recur->isValid($data, $state_data);
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
