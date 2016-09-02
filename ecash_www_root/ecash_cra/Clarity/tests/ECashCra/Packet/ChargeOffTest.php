<?php

require_once('test_setup.php');
require_once('ECashCra/Packet/ApplicationHelper.php');

class ECashCra_Packet_CharegeOffTest extends PHPUnit_Framework_TestCase
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
		$this->base = new ECashCra_Packet_ChargeOff($this->application->getApplication(), '2008-03-21', 300);
	}
	
	public function testGetXml()
	{
		$xml = $this->base->getXml();
		$xml->normalizeDocument();
		
		$expected = new SimpleXMLElement($this->application->getUpdateXml());
		$expected->QUERY->TYPE = 'chargeoff';
		$data_element = $expected->QUERY->DATA;
		$data_element->addChild('CHARGEOFFDATE', '2008-03-21');
		$data_element->addChild('CHARGEOFFAMOUNT', '300');
		
		$this->assertEquals(
			$expected->asXML(),
			$xml->saveXml()
		);
	}
}
?>