<?php

class VendorAPI_Actions_FailTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var VendorAPI_BasicDriver
	 */
	protected $driver;

	/**
	 * @var VendorAPI_Actions_Fail
	 */
	protected $action;

	protected $factory;

	public function setUp()
	{
		$this->markTestSkipped('This action is no longer used');

		$this->driver = $this->getMock(
			'VendorAPI_IDriver'
		);


		$table = $this->getMock('DB_Models_DatabaseModel_1', array('getColumns', 'getDatabaseInstance'), array(), '', FALSE);
		$table->expects($this->any())
			->method('getColumns')
			->will($this->returnValue(array()));

		$this->driver->expects($this->any())
			->method('getCompany')
			->will($this->returnValue("test"));

		$this->driver->expects($this->any())
			->method('getDataModelByTable')
			->will($this->returnValue($table));

		$this->factory = $this->getMock('VendorAPI_IApplicationFactory');

		$this->action = $this->getMock(
			'VendorAPI_Actions_Fail',
			array('hitStat','getLoanTypeId','getRuleSetId'),
			array($this->driver, $this->factory)
		);
	}

	public function testStatHitWhenSet()
	{
		$this->action->expects($this->once())
			->method('hitStat')
			->with($this->equalTo('test_stat1'));

		$data = array();
		$state = new VendorAPI_StateObject();
		$state->adverse_action = 'test_stat1';

		$actual_response = $this->action->execute($data, serialize($state));
		$this->checkResult($actual_response, VendorAPI_Response::SUCCESS, array());
	}

	public function testAdverseActionResetAfterStatIsHit()
	{
		$this->action->expects($this->once())
			->method('hitStat')
			->with($this->equalTo('test_stat1'));

		$data = array();
		$state = new VendorAPI_StateObject();
		$state->adverse_action = 'test_stat1';

		$actual_response = $this->action->execute($data, serialize($state));

		$state = $actual_response->getStateObject();
		$this->assertNull($state->adverse_action);
	}

	public function testStatHitNotHitWhenAdverseActionBlank()
	{
		$this->action->expects($this->never())
			->method('hitStat');

		$state = new VendorAPI_StateObject();
		$state->adverse_action = '';

		$data = array();
		$actual_response = $this->action->execute($data,serialize($state));
		$this->checkResult($actual_response, VendorAPI_Response::SUCCESS, array());
	}

	public function testStatHitNotHitWhenAdverseActionNotSet()
	{
		$this->action->expects($this->never())
			->method('hitStat');

		$state = new VendorAPI_StateObject();
		$data = array();
		$actual_response = $this->action->execute($data,serialize($state));
		$this->checkResult($actual_response, VendorAPI_Response::SUCCESS, array());
	}

	protected function checkResult(VendorAPI_Response $actual_response, $outcome, array $response_data)
	{
		$expected_response = new VendorAPI_Response(new VendorAPI_StateObject(), $outcome, $response_data);

		$this->assertEquals($expected_response->getOutcome(), $actual_response->getOutcome());
		$this->assertEquals($expected_response->getResult(), $actual_response->getResult());
	}
}

?>
