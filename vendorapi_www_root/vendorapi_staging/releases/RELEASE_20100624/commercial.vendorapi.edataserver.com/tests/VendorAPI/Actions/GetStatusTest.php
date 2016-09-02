<?php

class VendorAPI_Actions_GetStatusTest extends VendorApiBaseTest
{
	protected $_action;
	protected $_driver;
	protected $_app_factory;
	protected $test_app_id;
	protected $_state;

	public function setUp()
	{
		$this->test_app_id = 99999;
		$this->_driver = $this->getMock('VendorAPI_IDriver');
		$this->_app_factory = $this->getMock('VendorAPI_IApplicationFactory');

		$this->_action = new VendorAPI_Actions_GetStatus($this->_driver, $this->_app_factory);
		$context = new VendorAPI_CallContext();
		$context->setApiAgentId(1);
		$context->setCompanyId(1);
		$this->_action->setCallContext($context);

		$this->_state = new VendorAPI_StateObject();
		$this->_state->createPart('application');
		$this->_state->application->application_status = "confirmed::prospect::*root";

		$application = $this->getMock('VendorAPI_IApplication');
		$application->expects($this->once())->method('getApplicationStatusId')
			->will($this->returnValue(1));
		$application->expects($this->once())->method('getApplicationStatus')
			->will($this->returnValue("confirmed::prospect::*root"));

		$this->setGetApplicationExpectation($this->_app_factory, $this->test_app_id, $application);
	}

	public function testSuccess()
	{
		$this->_app_factory->expects($this->once())->method('createStateObject')
			->with($this->test_app_id, $this->isInstanceOf('VendorAPI_CallContext'))
			->will($this->returnValue($this->_state));
		$result = $this->_action->execute(99999, serialize($this->getMock('VendorAPI_StateObject', array(), array(), '', FALSE)));
		$this->assertTrue($result instanceof VendorAPI_Response);
		$resultArray = $result->toArray();
		$this->assertEquals(VendorAPI_Response::SUCCESS, $resultArray['outcome']);
	}

	public function testFromStateObject()
	{
		$this->_app_factory->expects($this->never())->method('createStateObject');
		$result = $this->_action->execute($this->test_app_id, serialize($this->_state))->toArray();
		$this->assertEquals(1, $result['result']['status_id']);
		$this->assertEquals('confirmed::prospect::*root', $result['result']['status']);
	}
}
