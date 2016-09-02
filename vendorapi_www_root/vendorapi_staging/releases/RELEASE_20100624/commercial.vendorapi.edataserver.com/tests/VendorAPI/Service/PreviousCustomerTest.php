<?php

class VendorAPI_Service_PreviousCustomerTest extends PHPUnit_Framework_TestCase
{
	protected $_action;
	protected $_driver;
	protected $_state;
	protected $_response;
	protected $_timer;
	
	/**
	 * @var VendorAPI_Service
	 */
	protected $_service;

	protected function setUp()
	{
		$this->_state = new VendorAPI_StateObject();
		$this->_response = new VendorAPI_Response($this->_state, VendorAPI_Response::SUCCESS, array());
		$this->_timer = $this->getMock('VendorAPI_RequestTimer', array(), array(), '', FALSE);
		
		$this->_action = $this->getMock('VendorAPI_Actions_PreviousCustomer', array('execute'), array(), '', FALSE);
		$this->_action->expects($this->any())
			->method('execute')
			->will($this->returnValue($this->_response));

		$this->_driver = $this->getMock('VendorAPI_IDriver');
		$this->_driver->expects($this->any())
			->method('getAction')
			->will($this->returnValue($this->_action));
		$this->_driver->expects($this->any())
			->method('getTimer')
			->will($this->returnValue($this->_timer));

		$this->_service = new VendorAPI_Service($this->_driver, new VendorAPI_CallContext());
	}

	protected function tearDown()
	{
		$this->_action = NULL;
		$this->_driver = NULL;
		$this->_service = NULL;
		$this->_state = NULL;
		$this->_timer = NULL;
	}

	public function testReturnsArray()
	{
		$response = $this->_service->previousCustomer(array());
		$this->assertType('array', $response);
	}

	public function testCallsGetActionWithCorrectName()
	{
		$this->_driver->expects($this->once())
			->method('getAction')
			->with('PreviousCustomer')
			->will($this->returnValue($this->_action));
		$this->_service->previousCustomer(array());
	}

	public function testCallsExecuteWithData()
	{
		$data = array(
			'ssn' => 123456789,
		);

		$this->_action->expects($this->once())
			->method('execute')
			->with($data)
			->will($this->returnValue($this->_response));
		$this->_service->previousCustomer($data);
	}
}

?>