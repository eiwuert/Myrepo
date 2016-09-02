<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test case for the withheld Email minimum recur rule.
 *
 * @author Rob Voss <rob.voss@sellingsource.com>
 */
class OLPBlackbox_Rule_MinimumRecur_WithheldEmailTest extends PHPUnit_Extensions_Database_TestCase
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
			array('test1@test.com', 'CM1', 30, FALSE), // 1 Email within 30 days
			array('test1@test.com', 'CM1', 29, TRUE), // No Email within 29 days
			array('test2@test.com', 'CAC', 30, FALSE), // 1 Email within 30 days
			array('test3@test.com', 'CM1', 30, FALSE, array('CM1', 'CM1_A')), // 1 SSN within 30 days on aliased CM1_A target
		);
	}

	/**
	 * Tests the isValid walkthrough for email minimum recur.
	 *
	 * @param string $email the email to use for the test
	 * @param string $campaign_name the campaign name to use for the test
	 * @param int $rule_value the rule value (days) to use for the test
	 * @param bool $expected_value the expecte value returned from isValid
	 * @param bool $properties optional array of properties for mocking getProperties
	 * @dataProvider isValidDataProvider
	 * @return void
	 */
	public function testIsValid($email, $campaign_name, $rule_value, $expected_value, $properties='')
	{
		$this->markTestIncomplete("database queries don't work with new schemas");
		
		$current_time = '2008-08-19 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-$rule_value days");

		$data = new OLPBlackbox_Data();
		$data->email_primary = $email;

		$state_data = new OLPBlackbox_CampaignStateData(array('campaign_name' => $campaign_name));

		$withheldemail_min_recur = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_WithheldEmail',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitEvent', 'hitStat', 'getProperties')
		);
		// Forces us to use out db connection
		$withheldemail_min_recur->expects($this->once())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Forces us to use our db name
		$withheldemail_min_recur->expects($this->once())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));
		// Forces the current date
		$withheldemail_min_recur->expects($this->any())->method('getQueryDate')
			->will($this->returnValue($query_date));
		// Return the expected aliases
		if (!$properties) $properties = array($campaign_name);
		$withheldemail_min_recur->expects($this->any())->method('getProperties')->will($this->returnValue($properties));
		
		$withheldemail_min_recur->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'email_primary',
				Blackbox_StandardRule::PARAM_VALUE => $rule_value
			)
		);

		$valid = $withheldemail_min_recur->isValid($data, $state_data);
		$this->assertEquals($expected_value, $valid);
		Cache_Memcache::getInstance()->delete($withheldemail_min_recur->getCacheKey());
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
		$data->email_primary = 'test2@test.com';

		$state_data = new OLPBlackbox_CampaignStateData(array('campaign_name' => 'CAC'));

		$withheldemail_min_recur = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_WithheldEmail',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitEvent', 'hitStat')
		);
		// Forces us to use out db connection
		$withheldemail_min_recur->expects($this->once())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Forces us to use our db name
		$withheldemail_min_recur->expects($this->once())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));
		// Forces the current date
		$withheldemail_min_recur->expects($this->any())->method('getQueryDate')
			->will($this->returnValue($query_date));

		$withheldemail_min_recur->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'email_primary',
				Blackbox_StandardRule::PARAM_VALUE => 30
			)
		);

		$valid = $withheldemail_min_recur->isValid($data, $state_data);
		
		// 1 is the value that will be set in memcache
		$this->assertEquals(1, Cache_Memcache::getInstance()->get($withheldemail_min_recur->getCacheKey()));
		Cache_Memcache::getInstance()->delete($withheldemail_min_recur->getCacheKey());
	}

	/**
	 * Tests memcache to see that the value for the check is not stored
	 * when the check passes.
	 *
	 * @return void
	 */
	public function testMemcachePassedCheck()
	{
		$this->markTestIncomplete("database queries don't work with new schemas");
		$current_time = '2008-08-19 12:00:00';
		$query_date = date_create($current_time);
		$query_date->modify("-30 days");

		$data = new OLPBlackbox_Data();
		$data->email_primary = 'test1@test.com';

		$state_data = new OLPBlackbox_CampaignStateData(array('campaign_name' => 'CAC'));

		$email_min_recur = $this->getMock(
			'OLPBlackbox_Rule_MinimumRecur_WithheldEmail',
			array('getDbInstance', 'getDbName', 'getQueryDate', 'hitEvent', 'hitStat')
		);
		// Forces us to use out db connection
		$email_min_recur->expects($this->once())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Forces us to use our db name
		$email_min_recur->expects($this->once())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));
		// Forces the current date
		$email_min_recur->expects($this->any())->method('getQueryDate')
			->will($this->returnValue($query_date));

		$email_min_recur->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'email_primary',
				Blackbox_StandardRule::PARAM_VALUE => 30
			)
		);

		$valid = $email_min_recur->isValid($data, $state_data);
		
		$this->assertFalse(Cache_Memcache::getInstance()->get($email_min_recur->getCacheKey()));
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
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/Withheld.Email.fixture.xml');
	}
}
?>
