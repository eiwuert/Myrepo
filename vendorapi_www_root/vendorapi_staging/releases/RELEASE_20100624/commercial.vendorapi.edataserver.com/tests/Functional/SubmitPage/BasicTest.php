<?php

class Functional_SubmitPage_BasicTest extends FunctionalTestCase
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

	public function testOutcomeIsSuccess()
	{
		//$data = unserialize(file_get_contents(dirname(__FILE__).'/_fixture/'.$this->_company.'_basic.inc'));
		$args = array(
			array(),
			$this->getStateObject('state_object/basic_'.$this->_company), // state object
		);

		$r = $this->_api->executeAction('submitPage', $args, FALSE);
		$this->assertEquals(1, $r['outcome']);
	}

	public function getDataset()
	{
		return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet(array());
	}
}

?>
