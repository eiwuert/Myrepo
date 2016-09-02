<?php

class Functional_GetPage_BasicTest extends FunctionalTestCase
{
	protected $_enterprise;
	protected $_company;
	protected $_user;
	protected $_pass;
	protected $_api;

	public function setUp()
	{
		$this->_enterprise = $GLOBALS['enterprise'];
		$this->_company = $GLOBALS['company'];
		$this->_user = $GLOBALS['api_user'];
		$this->_pass = $GLOBALS['api_pass'];

		parent::setUp();

		$this->_api = TestAPI::getInstance(
			$this->_enterprise,
			$this->_company,
			'DEV',
			$this->_user,
			$this->_pass
		);
	}

	public function tearDown()
	{
		$this->_api = NULL;
	}

	public function testBasicApplication()
	{
		$args = array(
			1355006618,
			'ent_online_confirm_legal',
			$this->getStateObject('state_object/basic_'.$this->_company), // state object
		);
		$r = $this->_api->executeAction('getPage', $args, FALSE);

		$this->assertEquals(1, $r['outcome']);
		$state = unserialize($r['state_object']);
		$this->assertTrue($state instanceof VendorAPI_StateObject);
		$this->assertTrue(is_null($r['result']['documents']));
		$this->assertEquals(array(150, 200, 250, 300), $r['result']['fund_amounts']);
		$this->assertEquals(150, $r['result']['qualify_info']['max_loan_amount']);
		$this->assertEquals(300, $r['result']['qualify_info']['loan_amount']);
		$this->assertEquals(90, $r['result']['qualify_info']['finance_charge']);
		$this->assertEquals(390, $r['result']['qualify_info']['total_payment']);
		$this->assertTrue(!empty($r['result']['qualify_info']['apr']));
	}

	public function getDataset()
	{
		return $this->getBaseFixture();
	}

	protected function getBaseFixture()
	{
		return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet(array());
	}
}
?>
