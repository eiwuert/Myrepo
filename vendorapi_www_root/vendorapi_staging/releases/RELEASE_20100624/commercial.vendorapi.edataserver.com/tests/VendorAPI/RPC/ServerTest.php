<?php

/**
 * Test Case for custom implementation of the RPC Server
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_RPC_ServerTest extends PHPUnit_Framework_TestCase
{
	protected $_log;
	protected $_server;

	protected function setUp()
	{
		$this->_log = $this->getMock('Log_ILog_1', array('write'));
		$this->_server = new VendorAPI_RPC_Server('VendorAPI_RPC_ServerTest_Service', $this->_log, NULL, FALSE);
	}

	protected function tearDown()
	{
		// make sure we use PHPUnit's error handler after RPC testing
		restore_error_handler();
	}

	/**
	 * Tests that uncaught exceptions coming from service calls are intercepted and logged.
	 *
	 * @return NULL
	 */
	public function testCallExceptionLogging()
	{
		$this->_log->expects($this->once())
			->method('write')
			->with($this->matchesRegularExpression('#^RPC Error in #', $this->equalTo(Log_ILog_1::LOG_CRITICAL)));

		$call = new Rpc_Call_1();
		$call->addMethod('test', 'testCall1', array());

		$this->_server->processCall($call);
	}

	/**
	 * Tests that clean calls won't log.
	 *
	 * @return NULL
	 */
	public function testSuccessfulCallDoesNotLog()
	{
		$this->_log->expects($this->never())
			->method('write');

		$call = new Rpc_Call_1();
		$call->addMethod('test', 'testCall2', array());

		$this->_server->processCall($call);
	}

	public function testExtendedExceptionsAreTranslatedToException()
	{
		$call = new Rpc_Call_1();
		$call->addMethod('test', 'testCall3', array());

		$res = $this->_server->processCall($call);
		$this->assertEquals('Exception', get_class($res['test'][1]));
	}
}

class VendorAPI_RPC_ServerTest_Exception extends Exception {}

/**
 * A test service
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_RPC_ServerTest_Service
{
	/**
	 * An exception call
	 *
	 * @return NULL
	 * @throws Exception
	 */
	public function testCall1()
	{
		throw new Exception("Test Exception");
	}

	/**
	 * A non-exception call
	 *
	 * @return NULL
	 */
	public function testCall2()
	{
		//do nothing
	}

	/**
	 * An extended exception call
	 * @return NULL
	 * @throws VendorAPI_RPC_ServerTest_Exception
	 */
	public function testCall3()
	{
		throw new VendorAPI_RPC_ServerTest_Exception();
	}
}

?>