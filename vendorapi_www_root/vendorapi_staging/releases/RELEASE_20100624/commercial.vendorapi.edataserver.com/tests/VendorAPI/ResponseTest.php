<?php

class VendorAPI_ResponseTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var VendorAPI_StateObject
	 */
	protected $_state;

	protected function setUp()
	{
		$this->_state = new VendorAPI_StateObject();
	}

	protected function tearDown()
	{
		$this->_state = NULL;
	}

	public function testSuccessHasNoError()
	{
		$r = new VendorAPI_Response(
			$this->_state,
			VendorAPI_Response::SUCCESS,
			array('woot'),
			'This should not appear'
		);
		$array = $r->toArray();

		$this->assertArrayNotHasKey('error', $array);
		$this->assertEquals(VendorAPI_Response::SUCCESS, $r->getOutcome());
		$this->assertTrue($r->getStateObject() instanceof VendorAPI_StateObject);
	}

	public function testErrorHasError()
	{
		$error_string = 'This should appear';

		$r = new VendorAPI_Response(
			$this->_state,
			VendorAPI_Response::ERROR,
			array('woot'),
			$error_string
		);
		$array = $r->toArray();

		$this->assertArrayHasKey('error', $array);
		$this->assertEquals($error_string, $r->getError());
		$this->assertEquals(VendorAPI_Response::ERROR, $r->getOutcome());
	}
}

?>