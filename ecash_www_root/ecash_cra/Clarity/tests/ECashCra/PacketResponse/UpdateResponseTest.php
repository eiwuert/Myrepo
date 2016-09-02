<?php

require_once('test_setup.php');

class ECashCra_PacketResponse_UpdateResponseTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var ECashCra_PacketResponse_UpdateResponse
	 */
	protected $response;
	
	public function setUp()
	{
		$this->response = new ECashCra_PacketResponse_UpdateResponse();
	}
	
	protected function loadSuccessXml()
	{
		$this->response->loadXml(
			"<?xml version=\"1.0\" encoding=\"utf8\"?>\n".
			"<CRAResponse>".
				"<Version>1.0</Version>".
				"<TransactionID>123456789</TransactionID>".
				"<Response>success</Response>".
			"</CRAResponse>"
		);
	}
	
	protected function loadFailedXml()
	{
		$this->response->loadXml(
			"<?xml version=\"1.0\" encoding=\"utf8\"?>\n".
			"<CRAResponse>".
				"<Version>1.0</Version>".
				"<TransactionID>123456789</TransactionID>".
				"<Response>".
					"<ErrorCode>500</ErrorCode>".
					"<ErrorMsg>General Error</ErrorMsg>".
				"</Response>".
			"</CRAResponse>"
		);
	}
	
	public function testIsSuccessTrue()
	{
		$this->loadSuccessXml();
		$this->assertTrue($this->response->isSuccess());
	}
	
	public function testIsSuccessFalse()
	{
		$this->loadFailedXml();
		$this->assertFalse($this->response->isSuccess());
	}
	
	public function testGetTransactionId()
	{
		$this->loadSuccessXml();
		$this->assertEquals('123456789', $this->response->getTransactionId());
	}
	
	public function testGetErrorCode()
	{
		$this->loadFailedXml();
		$this->assertEquals('500', $this->response->getErrorCode());
	}
	
	public function testGetErrorCodeSuccess()
	{
		$this->loadSuccessXml();
		$this->assertNull($this->response->getErrorCode());
	}
	
	public function testGetErrorMsg()
	{
		$this->loadFailedXml();
		$this->assertEquals('General Error', $this->response->getErrorMsg());
	}
	
	public function testGetErrorMsgSuccess()
	{
		$this->loadSuccessXml();
		$this->assertNull($this->response->getErrorMsg());
	}
}
?>