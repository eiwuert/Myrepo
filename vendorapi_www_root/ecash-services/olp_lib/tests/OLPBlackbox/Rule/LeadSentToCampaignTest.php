<?php
/**
 * Test the multi campaign recur rules
 */
class OLPBlackbox_Rule_LeadSentToCampaignTest extends PHPUnit_Extensions_Database_TestCase
{
	const PASS_SSN = '800991111';
	const PASS_EMAIL = '2@tssmasterd.com';
	const FAIL_SSN = '800991113';
	const FAIL_EMAIL = '1@tssmasterd.com';

	const THRESHOLD = 2;
	const PROPERTY_SHORT_1 ='pp1';
	const PROPERTY_SHORT_2 ='pp2';
	const DAYS_TO_LOOK = 3;

	const START_DATE = '2009-11-10';

	protected $_rule;
	protected $_factory;

	public function setup()
	{
		parent::setup();
		$this->_factory = $this->getMock('OLP_Factory', array('getModel'), array(), '', FALSE);

		$this->_rule = $this->getMock('FakeLeadSentToCampaignRecur',
			array('getTime'), array($this->_factory));

		$this->_rule->expects($this->any())->method('getTime')
			->will($this->returnValue(strtotime(self::START_DATE)));
	}


	public function testCanRunPassForSSN()
	{
		$state_data = new OLPBlackbox_StateData();
		$bbx_data = new OLPBlackbox_Data();
		$bbx_data->social_security_number_encrypted = self::PASS_SSN;
		$this->setupForSSN();

		$this->assertTrue($this->_rule->canRun($bbx_data, $state_data));
	}

	public function testCanRunFailForSSN()
	{
		$state_data = new OLPBlackbox_StateData();
		$bbx_data = new OLPBlackbox_Data();
		$this->setupForSSN();
		$this->assertFalse($this->_rule->canRun($bbx_data, $state_data));
	}

	public function testCanRunPassForEmail()
	{
		$state_data = new OLPBlackbox_StateData();
		$bbx_data = new OLPBlackbox_Data();
		$bbx_data->email_primary = self::PASS_EMAIL;
		$this->setupForEmail();

		$this->assertTrue($this->_rule->canRun($bbx_data, $state_data));
	}

	public function testCanRunFailForEmail()
	{
		$state_data = new OLPBlackbox_StateData();
		$bbx_data = new OLPBlackbox_Data();
		$this->setupForEmail();

		$this->assertFalse($this->_rule->canRun($bbx_data, $state_data));
	}

	public function testSSNPass()
	{
		$state_data = new OLPBlackbox_StateData();
		$bbx_data = new OLPBlackbox_Data();
		$bbx_data->social_security_number_encrypted = self::PASS_SSN;
		$this->setupForSSN();
		$this->mockFactoryForModelReturn();
		$this->assertTrue($this->_rule->runRule($bbx_data, $state_data));
	}

	public function testSSNFail()
	{
		$state_data = new OLPBlackbox_StateData();
		$bbx_data = new OLPBlackbox_Data();
		$bbx_data->social_security_number_encrypted = self::FAIL_SSN;
		$this->setupForSSN();
		$this->mockFactoryForModelReturn();
		$this->assertFalse($this->_rule->runRule($bbx_data, $state_data));
	}

	public function testEmailPass()
	{
		$state_data = new OLPBlackbox_StateData();
		$bbx_data = new OLPBlackbox_Data();
		$bbx_data->email_primary = self::PASS_EMAIL;
		$this->setupForEmail();
		$this->mockFactoryForModelReturn();
		$this->assertTrue($this->_rule->runRule($bbx_data, $state_data));
	}

	public function testEmailFail()
	{
		$state_data = new OLPBlackbox_StateData();
		$bbx_data = new OLPBlackbox_Data();
		$bbx_data->email_primary = self::FAIL_EMAIL;
		$this->setupForEmail();
		$this->mockFactoryForModelReturn();
		$this->assertFalse($this->_rule->runRule($bbx_data, $state_data));
	}

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
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/MultiCampaignRecur.fixture.xml');
	}

	protected function mockFactoryForModelReturn()
	{
		$mock_crypt = $this->getMock('Security_ICrypt_1');
		$this->_factory->expects($this->any())->method('getModel')
			->with('BlackboxPost')
			->will($this->returnValue(new OLP_Models_BlackboxPost(TEST_DB_DATABASE(), $mock_crypt)));
	}


	protected function setupForSSN()
	{
		$params = array(
			Blackbox_StandardRule::PARAM_FIELD => "",
			Blackbox_StandardRule::PARAM_VALUE => array(
				'field' => 'ssn',
				'threshold' => self::THRESHOLD,
				'number_of_days' => self::DAYS_TO_LOOK,
				'targets' => 'pp1,pp2'
			)
		);
		$this->_rule->setupRule($params);
	}

	protected function setupForEmail()
	{
		$params = array(
			Blackbox_StandardRule::PARAM_FIELD => "",
			Blackbox_StandardRule::PARAM_VALUE => array(
				'field' => 'email',
				'threshold' => self::THRESHOLD,
				'number_of_days' => self::DAYS_TO_LOOK,
				'targets' => 'pp1,pp2'
			)
		);
		$this->_rule->setupRule($params);
	}

}

class FakeLeadSentToCampaignRecur extends OLPBlackbox_Rule_LeadSentToCampaign
{
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return parent::runRule($data, $state_data);
	}

	public function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return parent::canRun($data, $state_data);
	}
}
