<?php

require_once('test_setup.php');
require_once('ECashCra/Packet/ApplicationHelper.php');

class ECashCra_Packet_PaidOffTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var ECashCRA_Packet_Cancellation
	 */
	protected $base;
	
	/**
	 * @var Tests_ApplicationHelper
	 */
	protected $application;
	
	public function setUp()
	{
		$this->application = new Tests_ApplicationHelper();
		$this->base = new ECashCra_Packet_PaidOff($this->application->getApplication(), '2008-03-21');
	}
	
	public function testGetXml()
	{
		$xml = $this->base->getXml();
		$xml->normalizeDocument();
		
		$expected = new SimpleXMLElement($this->application->getUpdateXml());
		$expected->QUERY->TYPE = 'paid_off';
		$data_element = $expected->QUERY->DATA;
		$data_element->addChild('PAIDOFFDATE', '2008-03-21');
		
		$this->assertEquals(
			$expected->asXML(),
			$xml->saveXml()
		);
	}
}
?>