<?php

class VendorAPI_Actions_FindDocumentTest extends PHPUnit_Framework_TestCase
{
	protected $_driver;
	protected $_condor;
	public function setUp()
	{
		$this->markTestSkipped('This action is no longer used');
		$this->_driver = $this->getMock('VendorAPI_IDriver');
		$this->_condor = $this->getMock('VendorAPI_IDocument');
	}

	public function testReturnsSuccess()
	{
		$action = new VendorAPI_Actions_FindDocument($this->_driver, $this->_condor);
		$response = $action->execute(99999, 'Loan Document')->toArray();
		$this->assertEquals(1, $response['outcome']);
	}

	public function testFindsDocument()
	{
		$action = new VendorAPI_Actions_FindDocument($this->_driver, $this->_condor);
		$this->_condor->expects($this->once())->method('findDocument')
			->with(99999, 'Loan Document')
			->will($this->returnValue(12));
		$response = $action->execute(99999, 'Loan Document')->toArray();
		$this->assertEquals(1, $response['outcome']);
		$this->assertEquals(12, $response['result']['archive_id']);
	}

	public function testErrorOnException()
	{
		$action = new VendorAPI_Actions_FindDocument($this->_driver, $this->_condor);
		$this->_condor->expects($this->once())->method('findDocument')
			->with(99999, 'Loan Document')
			->will($this->throwException(new InvalidArgumentException("Invalid app id.")));
		$response = $action->execute(99999, 'Loan Document')->toArray();
		$this->assertEquals(0, $response['outcome']);
	}
}