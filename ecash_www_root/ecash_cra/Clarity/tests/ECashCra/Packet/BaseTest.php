<?php

require_once('test_setup.php');
require_once('ECashCra/Packet/ApplicationHelper.php');

class ECashCra_Packet_BaseTest extends PHPUnit_Framework_TestCase
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
		$this->base = $this->getMock('ECashCra_Packet_Base', array('getPacketType', 'buildData'), array($this->application->getApplication()));
	}
	
	public function testGetXml()
	{
		$this->base->expects($this->once())
			->method('buildData')
			->withAnyParameters()
			->will($this->returnValue(new DOMElement('DATA')));
		
		$this->base->expects($this->once())
			->method('getPacketType')
			->withAnyParameters()
			->will($this->returnValue('TEST'));
		
		$xml = $this->base->getXml();
		$xml->normalizeDocument();
		
		$this->assertEquals(
			$this->application->getBaseXml(),
			$xml->saveXml()
		);
	}
}
?>