<?php
/**
 * Tests the base BadCustomer rule abstract class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class OLPBlackbox_Rule_BadCustomerTestBase extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * Data object.
	 *
	 * @var OLPBlackbox_Data
	 */
	protected $bbx_data;
	
	/**
	 * State object.
	 *
	 * @var OLPBlackbox_CampaignStateData
	 */
	protected $state;
	
	/**
	 * The rule object to test.
	 *
	 * @var OLPBlackbox_Rule_BadCustomer
	 */
	protected $rule;
	
	/**
	 * Test case setup.
	 *
	 * @return void
	 */
	public function setUp()
	{
		parent::setUp();
		
		$this->bbx_data = new OLPBlackbox_Data();
		$this->state = new OLPBlackbox_CampaignStateData();
		$this->rule = $this->getMock(
			'OLPBlackbox_Rule_BadCustomer_ZipCash',
			array('getCacheValue', 'getOLPConnection')
		);
		
		$this->rule->expects($this->any())
			->method('getOLPConnection')
			->will($this->returnValue(TEST_DB_DATABASE()));
			
		$this->rule->setupRule(
			array(OLPBlackbox_Rule_BadCustomer::PARAM_FIELD => array('social_security_number', 'email_primary'))
		);
	}
	
	/**
	 * Tests that if we find data in the bad_customer table, the rule fails.
	 *
	 * @return void
	 */
	public function testFindingDataRuleFails()
	{
		$this->bbx_data->social_security_number = '555661234';
		$this->bbx_data->email_primary = 'john.doe@example.com';
		
		$this->rule->expects($this->atLeastOnce())
			->method('getCacheValue')
			->will($this->returnValue(FALSE));
		
		$this->assertFalse($this->rule->isValid($this->bbx_data, $this->state));
	}
	
	/**
	 * Tests that if no data if found, the rule passes.
	 *
	 * @return void
	 */
	public function testNotFindingDataRulePasses()
	{
		$this->bbx_data->social_security_number = '555665555';
		$this->bbx_data->email_primary = 'jane.doe@example.com';
		
		$this->rule->expects($this->any())
			->method('getCacheValue')
			->will($this->returnValue(FALSE));
			
		$this->assertTrue($this->rule->isValid($this->bbx_data, $this->state));
	}
	
	/**
	 * Tests if data if found in the cache with bad customers, the rule fails.
	 *
	 * @return void
	 */
	public function testFindDataInCacheRuleFails()
	{
		$this->bbx_data->social_security_number = '555665555';
		$this->bbx_data->email_primary = 'jane.doe@example.com';
		
		$this->rule->expects($this->any())
			->method('getCacheValue')
			->will($this->returnValue('Y'));
		
		$this->assertFalse($this->rule->isValid($this->bbx_data, $this->state));
	}
	
	/**
	 * Tests that if good customers are found in cache, the rule passes.
	 *
	 * @return void
	 */
	public function testFindDataInCacheRulePasses()
	{
		$this->bbx_data->social_security_number = '555665555';
		$this->bbx_data->email_primary = 'jane.doe@example.com';
		
		$this->rule->expects($this->any())
			->method('getCacheValue')
			->will($this->returnValue('N'));
		
		$this->assertTrue($this->rule->isValid($this->bbx_data, $this->state));
	}
	
	/**
	 * Returns the tests connection.
	 *
	 * @return PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
	 */
	protected function getConnection()
	{
		return $this->createDefaultDBConnection(TEST_DB_PDO(), TEST_GET_DB_INFO()->name);
	}
	
	/**
	 * Returns the test case's data set.
	 *
	 * @return PHPUnit_Extensions_Database_DataSet_XmlDataSet
	 */
	protected function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/BadCustomer.xml');
	}
}
