<?php

require_once('test_setup.php');

class ECashCra_Scripts_BaseTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var ECashCra_Api
	 */
	protected $mock_api;
	
	/**
	 * @var Test_Base_Extension
	 */
	protected $script;
	
	public function setUp()
	{
		$this->mock_api = $this->getMock('ECashCra_Api', array(), array('http://test', 'user', 'pass'));
		$this->script = new Test_Base_Extension($this->mock_api);
	}
	
	public function testGetApi()
	{
		$this->assertSame($this->mock_api, $this->script->getApi());
	}
	
	public function testSetExportDate()
	{
		$this->script->setExportDate('2008-03-21');
		$this->assertAttributeEquals('2008-03-21', 'date', $this->script);
	}

	public function testLogMessageFailed()
	{
		$response = $this->getMock('ECashCra_PacketResponse_UpdateResponse');
		$response->expects($this->any())
			->method('getErrorCode')
			->will($this->returnValue('500'));
		
		$response->expects($this->any())
			->method('getErrorMsg')
			->will($this->returnValue('General Error'));
		
		$response->expects($this->any())
			->method('getTransactionId')
			->will($this->returnValue('1234'));
		
		ob_start();
		$this->script->logMessage(false, 10001, $response);
		$output = ob_get_clean();
		
		$this->assertEquals(
			"FAIL - externalid: 10001\ttransactionid: 1234\n\t[500] General Error\n",
			$output
		);
	}
	
	public function testLogMessageSuccess()
	{
		$response = $this->getMock('ECashCra_PacketResponse_UpdateResponse');
		$response->expects($this->any())
			->method('getTransactionId')
			->will($this->returnValue('1234'));
		
		ob_start();
		$this->script->logMessage(true, 10001, $response);
		$output = ob_get_clean();
		
		$this->assertEquals(
			"OK   - externalid: 10001\ttransactionid: 1234\n",
			$output
		);
	}
	
	public function testCreateResponse()
	{
		$this->assertThat(
			$this->script->createResponse(),
			$this->isInstanceOf('ECashCra_PacketResponse_UpdateResponse')
		);
	}
}

class Test_Base_Extension extends ECashCra_Scripts_Base 
{
	public function logMessage($success, $external_id, ECashCRA_IPacketResponse $response)
	{
		return parent::logMessage($success, $external_id, $response);
	}
	
	public function createResponse()
	{
		return parent::createResponse();
	}
}

?>