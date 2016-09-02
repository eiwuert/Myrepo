<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * Test the OLPBlackbox_Vetting_Rule_SSNCheck class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Vetting_Rule_SSNCheckTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * SSN we implant in the fixture XML to test against.
	 *
	 * @var string
	 */
	protected static $good_ssn;
	
	/**
	 * SSN that should fall outside the boundaries of the SSN check.
	 *
	 * @var string
	 */
	protected static $bad_ssn;
	
	/**
	 * The XML file we'll populate the Database with.
	 *
	 * @var string
	 */
	protected static $fixture_file;
	
	/**
	 * Config object for the {@see crypt} property.
	 *
	 * @var Crypt_Config
	 */
	protected static $crypt_config;
	
	/**
	 * Crypt object used to encrypted SSNs
	 *
	 * @var Crypt_Singleton
	 */
	protected static $crypt;
	
	/**
	 * Make sure we have the classes/data fixtures to run the tests for this class.
	 *
	 * @return void
	 */
	public function setUp()
	{
		parent::setUp();
		if (!class_exists('Crypt_Config') || !class_exists('Crypt_Singleton'))
		{
			$this->markTestIncomplete('missing classes for SSNCheck test');
		}		
	}
	
	/**
	 * Sets up the used to set up a testing database.
	 * 
	 * Required for subclassing {@see PHPUnit_Extensions_Database_TestCase}.
	 *
	 * @return void
	 */
	protected function getConnection()
	{
		return $this->createDefaultDBConnection(TEST_DB_PDO(), TEST_GET_DB_INFO()->name);
	}
	
	/**
	 * Gathers the data used for the tests in this class.
	 *
	 * @return void
	 */
	protected function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/SSNCheck.fixture.xml');
	}	
	
	/**
	 * Provides data to the method {@see testSSNCheck}.
	 *
	 * @return array multidimentional array holding parameters 
	 * 	for the test method.
	 */
	public static function SSNCheckDataProvider()
	{
		$valid_data = new OLPBlackbox_Data();
		$invalid_data = new OLPBlackbox_Data();
		
		// these 
		
		// this data will fail because there's a date in the DB which is within 120 days
		$invalid_data->social_security_number_encrypted = '111223333';
		$invalid_data->application_id = 5;
		
		// this one should be valid
		$valid_data->social_security_number_encrypted = '123456789';
		$valid_data->application_id = 9;
		
		// test both encrypted and unencrypted fails and passes
		return array(
			array($invalid_data, FALSE),
			array($valid_data, TRUE)
		);
	}
	
	/**
	 * Tests SSN check.
	 *
	 * @param Blackbox_Data $data The data provided to test the rule.
	 * @param bool The expected result of isValid()
	 * @dataProvider SSNCheckDataProvider
	 * @return void
	 */
	public function testSSNCheck(Blackbox_Data $data, $expected_result)
	{
		$rule = $this->getMock(
			'OLPBlackbox_Vetting_Rule_SSNCheck',
			array('getNow', 'getDb')
		);
		$rule->expects($this->any())
			->method('getNow')
			->will($this->returnValue('2008-03-25'));
			
		$db = TEST_DB_PDO();
		$rule->expects($this->any())
			->method('getDb')
			->will($this->returnValue($db));
			
		$init_data = array('campaign_name' => 'ca1', 'name' => 'ca');
		$state_data = new OLPBlackbox_CampaignStateData($init_data);
			
		$this->assertEquals($expected_result, $rule->isValid($data, $state_data));
	}
}

?>
