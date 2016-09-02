<?php

class VendorAPI_Actions_GetPageTest extends PHPUnit_Framework_TestCase
{
	protected $_action;
	protected $_driver;

	public function setUp()
	{
		//todo: This is because someone used the wrong casing for Webservices_Client_AppClient in the IApplication interface...that should be fixed and this removed
		class_exists('WebServices_Client_AppClient', TRUE);
		
		$xml = <<<XML
<?xml version="1.0"?>
<ruleset/>
XML;

		$doc = new DOMDocument();
		$doc->loadXML($xml);

		$this->_driver = $this->getMock('VendorAPI_IDriver');
		$this->_driver->expects($this->any())
			->method('getPageflowConfig')
			->will($this->returnValue($doc));

		$this->_driver->expects($this->any())
			->method('getStatProClient')
			->will($this->returnValue($this->getMock('VendorAPI_StatProClient', array(), array(), '', FALSE)));

		$rule_factory = $this->getMock('VendorAPI_CFE_IRulesetFactory');
		$rule_factory->expects($this->any())
			->method('getRuleset')
			->will($this->returnValue(array()));

		$app = $this->getMock('VendorAPI_IApplication');
		$app->expects($this->any())
			->method('getCfeContext')
			->will($this->returnValue(new ECash_CFE_ArrayContext(array())));

		$qi = new VendorAPI_QualifyInfo(100, 100, 1000, time(), time(), 50, 150);
		$app->expects($this->any())
			->method('calculateQualifyInfo')
			->will($this->returnValue($qi));

		$app_factory = $this->getMock('VendorAPI_IApplicationFactory');
		$app_factory->expects($this->any())
			->method('getApplication')
			->will($this->returnValue($app));
		$app_factory->expects($this->any())
			->method('createStateObject')
			->will($this->returnValue(new VendorAPI_StateObject()));

		$this->_action = new VendorAPI_Actions_GetPage($this->_driver, $rule_factory, $app_factory);
		$this->_action->setCallContext(new VendorAPI_CallContext());
	}

	public function testSuccess()
	{
		$result = $this->_action->execute(array(), '', NULL, serialize($this->getMock('VendorAPI_StateObject', array(), array(), '', FALSE)))->toArray();
		$this->assertEquals(1, $result['outcome']);
	}
}
