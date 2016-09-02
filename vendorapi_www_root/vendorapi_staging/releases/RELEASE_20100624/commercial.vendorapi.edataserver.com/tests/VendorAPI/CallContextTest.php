<?php

class VendorAPI_CallContextTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var VendorAPI_CallContext
	 */
	protected $_context;

	protected function setUp()
	{
		$this->_context = new VendorAPI_CallContext();
	}

	public function testAgentId()
	{
		$this->_context->setApiAgentId(10);
		
		$this->assertEquals(10, $this->_context->getApiAgentId());
	}
}

?>