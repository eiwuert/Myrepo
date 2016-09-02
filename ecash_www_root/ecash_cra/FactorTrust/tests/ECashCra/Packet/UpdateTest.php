<?php

require_once('test_setup.php');
require_once('ECashCra/Packet/ApplicationHelper.php');

class ECashCra_Packet_UpdateTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var ECashCra_Packet_Base
	 */
	protected $base;
	
	/**
	 * @var Tests_ApplicationHelper
	 */
	protected $application;
	
	public function setUp()
	{
		$this->application = new Tests_ApplicationHelper();
		$this->base = $this->getMock('ECashCra_Packet_Update', array('getPacketType', 'buildUpdateSection'), array($this->application->getApplication()));
	}
	
	public function testGetXml()
	{
		$this->base->expects($this->once())
			->method('buildUpdateSection')
			->withAnyParameters();
		
		$this->base->expects($this->once())
			->method('getPacketType')
			->withAnyParameters()
			->will($this->returnValue('TEST'));
		
		$xml = $this->base->getXml();
		$xml->normalizeDocument();
		
		$this->assertEquals(
			$this->application->getUpdateXml(),
			$xml->saveXml()
		);
	}
}
?>