<?php

class VendorAPI_Service_NoopTest extends PHPUnit_Framework_TestCase
{
	protected $_action;
	protected $_driver;
	protected $_service;
	protected $_call_context;
	protected $_timer;
	
	protected function setUp()
	{
		$this->_action = $this->getMock('VendorAPI_Actions_Noop', array('execute', 'setCallContext'), array(), '', FALSE);
		$log = $this->getMock('Log_ILog_1');
		$this->_timer = $this->getMock('VendorAPI_RequestTimer', array(), array(), '', FALSE);
		
		$this->_driver = $this->getMock('VendorAPI_IDriver');
		$this->_driver->expects($this->any())
			->method('getAction')
			->will($this->returnValue($this->_action));

		$this->_driver->expects($this->any())
			->method('getLog')
			->will($this->returnValue($log));

		$this->_driver->expects($this->any())
			->method('getTimer')
			->will($this->returnValue($this->_timer));
			
		$this->_call_context = new VendorAPI_CallContext();
		$this->_service = new VendorAPI_Service($this->_driver, $this->_call_context);
	}

	protected function tearDown()
	{
		$this->_action = NULL;
		$this->_driver = NULL;
		$this->_service = NULL;
		$this->_timer = NULL;
	}

	public function testNoopWithParameters()
	{
		$state = new VendorAPI_StateObject();
		$response = new VendorAPI_Response($state, VendorAPI_Response::SUCCESS, array('test'));

		$this->_action->expects($this->once())
			->method('execute')
			->with('test')
			->will($this->returnValue($response));
			
		$this->_action->expects($this->once())
			->method('setCallContext')
			->with($this->equalTo($this->_call_context));

		$this->_service->noop('test');
	}

	public function testNoopWithoutParameters()
	{
		$state = new VendorAPI_StateObject();
		$response = new VendorAPI_Response($state, VendorAPI_Response::SUCCESS, array('test'));

		$this->_action->expects($this->once())
			->method('execute')
			->with()
			->will($this->returnValue($response));

			
		$this->_action->expects($this->once())
			->method('setCallContext')
			->with($this->equalTo($this->_call_context));
		
		$this->_service->noop();
	}
}

?>