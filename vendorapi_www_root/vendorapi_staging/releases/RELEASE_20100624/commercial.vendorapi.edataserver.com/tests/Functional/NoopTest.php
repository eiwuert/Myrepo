<?php

class NoopTest extends FunctionalTestCase
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

	public function testNoop()
	{
		$r = $this->_api->executeAction('noop', array());
		$this->assertEquals(1, $r['outcome']);
	}

	public function getDataset()
	{
		return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet(array());
	}
}

?>