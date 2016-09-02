<?php

require_once('test_setup.php');
require_once('ECashCra/Packet/ApplicationHelper.php');

class ECashCra_Packet_FundUpdateTest extends PHPUnit_Framework_TestCase
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
		$this->base = new ECashCra_Packet_FundUpdate($this->application->getApplication());
	}
	
	public function testGetXml()
	{
		$xml = $this->base->getXml();
		$xml->normalizeDocument();
		
		$expected = new SimpleXMLElement($this->application->getUpdateXml());
		$expected->QUERY->TYPE = 'fund_update';
		$data_element = $expected->QUERY->DATA;
		$data_element->addChild('FUNDDATE', '2008-03-20');
		$data_element->addChild('FUNDAMOUNT', '300');
		$data_element->addChild('FUNDFEE', '90');
		$data_element->addChild('DUEDATE', '2008-04-01');
		
		$this->assertEquals(
			$expected->asXML(),
			$xml->saveXml()
		);
	}
}
?>
