<?php

class EndPoints_IndexTest extends PHPUnit_Framework_TestCase
{
	protected $_include_path;

	public function setUp()
	{
		if (!extension_loaded('runkit'))
		{
			$this->markTestSkipped('Runkit not loaded');
		}
		$this->_include_path = get_include_path();
	}

	public function tearDown()
	{
		set_include_path($this->_include_path);
	}

	public function testIndex()
	{
		$_REQUEST['username'] = 'test_user';
		$_REQUEST['password'] = 'test_pass';
		$_REQUEST['enterprise'] = 'test_enterprise';
		$_REQUEST['company'] = 'test_company';
		$_SERVER["ENVIRONMENT_MODE"] = 'test_mode';

		$mock_driver = $this->getMock('VendorAPI_IDriver');
		$mock_auth = $this->getMock('VendorAPI_IAuthenticator');

		$mock_driver->expects($this->any())
			->method('getAuthenticator')
			->will($this->returnValue($mock_auth));

		$mock_auth->expects($this->once())
			->method('authenticate')
			->with($this->equalTo('test_user'), $this->equalTo('test_pass'))
			->will($this->returnValue(TRUE));

		$mock_rpc = new MockFunction_Mock(array('VendorAPI_RPC_Server', '__construct'));
		$mock_rpc->expects($this->once())
			->with($this->logicalAnd(
				$this->isInstanceOf('VendorAPI_Service'),
				$this->attributeEqualTo('driver', $mock_driver),
				$this->attribute($this->isInstanceOf('VendorAPI_CallContext'), 'call_context')));

		require_once('TestBootLoader.php');
		VendorAPI_Loader::setExpectedValues('test_enterprise', 'test_company', 'test_mode');
		VendorAPI_Loader::setDriver($mock_driver);

		include('../www/index.php');

		VendorAPI_Loader::validateMethodCalls();
		MockFunction_Mock::verifyAllMocks();
	}
}

?>
