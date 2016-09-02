<?php

require_once('test_setup.php');
require_once('ECashCra/Packet/ApplicationHelper.php');

class ECashCra_Packet_TradelinePaymentsTest extends PHPUnit_Framework_TestCase
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
		$this->base = new ECashCra_Packet_TradelinePayments($this->application->getPayment());
	}
	
	public function testGetXml()
	{
		$xml = $this->base->getXml();
		$xml->normalizeDocument();
		
		$expected = new SimpleXMLElement($this->application->getBaseXml());
		$expected->QUERY->TYPE = 'payment';
		$data_element = $expected->QUERY->DATA;
		$data_element->addChild('PAYMENTID', '2002');
		$data_element->addChild('TYPE', 'DEBIT');
		$data_element->addChild('METHOD', 'ACH');
		$data_element->addChild('PAYMENTDATE', '2008-03-21');
		$data_element->addChild('AMOUNT', '90');
		$data_element->addChild('RETURNCODE', 'NSF');
		$data_element->addChild('BANKNAME', 'First Bank');
		$data_element->addChild('BANKABA', '123456789');
		$data_element->addChild('BANKACCTNUMBER', '123456');
		
		$this->assertEquals(
			$expected->asXML(),
			$xml->saveXml()
		);
	}
}
?>