<?php

require_once('test_setup.php');

class ECashCra_ApiTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var ECashCra_Api
	 */
	protected $api;
	
	public function setUp()
	{
	}
	
	public function testSendPacket()
	{
		$api = new ECashCra_Api('http://ecash_cra.ds60.tss/tests/succeed.php', 'test', 'pass');
		
		$xml = $this->buildTestPacket();
		$packet = $this->getMock('ECashCra_IPacket');
		$packet->expects($this->any())
			->method('getXml')
			->will($this->returnValue($xml));
		
		$response = new ECashCra_PacketResponse_UpdateResponse();
		
		$api->sendPacket($packet, $response);
		
		$this->assertEquals(
			"<?xml version=\"1.0\"?>\n".
			"<CRAINQUIRY>".
				"<AUTHENTICATION>".
					"<USERNAME>test</USERNAME>".
					"<PASSWORD>pass</PASSWORD>".
				"</AUTHENTICATION>".
				"<QUERY/>".
			"</CRAINQUIRY>\n",
			$xml->saveXML()
		);
		
		$this->assertEquals('123456789', $response->getTransactionId());
		$this->assertEquals(true, $response->isSuccess());
	}
	
	/**
	 * @expectedException ECashCra_ApiException
	 */
	public function testSendPacketBadResponse()
	{
		$api = new ECashCra_Api('http://ecash_cra.ds60.tss/tests/badresponse.php', 'test', 'pass');
		
		$xml = $this->buildTestPacket();
		$packet = $this->getMock('ECashCra_IPacket');
		$packet->expects($this->any())
			->method('getXml')
			->will($this->returnValue($xml));
		
		$response = new ECashCra_PacketResponse_UpdateResponse();
		
		$api->sendPacket($packet, $response);
	}
	
	
	/**
	 * @expectedException ECashCra_ApiException
	 */
	public function testSendPacketBadUrl()
	{
		$api = new ECashCra_Api('http://aplacethatdoesntexist', 'test', 'pass');
		
		$xml = $this->buildTestPacket();
		$packet = $this->getMock('ECashCra_IPacket');
		$packet->expects($this->any())
			->method('getXml')
			->will($this->returnValue($xml));
		
		$response = new ECashCra_PacketResponse_UpdateResponse();
		
		$api->sendPacket($packet, $response);
	}
	
	protected function buildTestPacket()
	{
		$xml = new DOMDocument('1.0', 'utf8');
		$xml->loadXML(
			"<CRAINQUIRY>".
				"<QUERY/>".
			"</CRAINQUIRY>"
		);
		
		return $xml;
	}
}
?>