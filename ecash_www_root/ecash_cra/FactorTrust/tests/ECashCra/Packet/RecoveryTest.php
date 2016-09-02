<?php

require_once('test_setup.php');
require_once('ECashCra/Packet/ApplicationHelper.php');

class ECashCra_Packet_RecoveryTest extends PHPUnit_Framework_TestCase
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
		$this->base = new ECashCra_Packet_Recovery($this->application->getApplication(), '2008-03-21', 50, 250);
	}
	
	public function testGetXml()
	{
		$xml = $this->base->getXml();
		$xml->normalizeDocument();
		
		$expected = new SimpleXMLElement($this->application->getUpdateXml());
		$expected->QUERY->TYPE = 'recovery';
		$data_element = $expected->QUERY->DATA;
		$data_element->addChild('RECOVERYDATE', '2008-03-21');
		$data_element->addChild('RECOVERYAMOUNT', '50');
		$data_element->addChild('REMAININGBALANCE', '250');
		
		$this->assertEquals(
			$expected->asXML(),
			$xml->saveXml()
		);
	}
}
?>