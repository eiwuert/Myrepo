<?php

class VendorAPI_Actions_NoopTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var VendorAPI_Actions_Noop
	 */
	protected $_noop;

	/**
	 * @var VendorAPI_Response
	 */
	protected $_response;

	protected function setUp()
	{
		$driver = $this->getMock('VendorAPI_IDriver');

		$this->_noop = new VendorAPI_Actions_Noop($driver);
		$this->_response = $this->_noop->execute();
	}

	protected function tearDown()
	{
		$this->_noop = NULL;
		$this->_response = NULL;
	}

	public function testNoopReturnsResponse()
	{
		$this->assertType('VendorAPI_Response', $this->_response);
	}

	public function testNoopResultWithNoInput()
	{
		$result = $this->_response->getResult();
		$this->assertEquals('Hello world', $result[0]);
	}

	public function testNoopResultWithInput()
	{
		$response = $this->_noop->execute('test');
		$result = $response->getResult();

		$this->assertEquals('test', $result[0]);
	}
}

?>