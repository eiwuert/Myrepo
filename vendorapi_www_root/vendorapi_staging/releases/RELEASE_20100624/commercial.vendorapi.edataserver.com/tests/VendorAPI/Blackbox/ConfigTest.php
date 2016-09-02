<?php

/**
 * Simple and boring, but verify that we can set the required configuration options
 *
 */
class VendorAPI_Blackbox_ConfigTest extends PHPUnit_Framework_TestCase
{
	protected $_config;

	protected function setUp()
	{
		$this->_config = new VendorAPI_Blackbox_Config();
		$this->_config->enterprise = 'clk';
		$this->_config->campaign = 'ca_t1';
		$this->_config->company = 'ca';
		$this->_config->is_enterprise = FALSE;
		$this->_config->blackbox_mode = VendorAPI_Blackbox_Config::MODE_BROKER;
	}

	protected function tearDown()
	{
		$this->_config = NULL;
	}

	public function testIsEnterprise()
	{
		$this->assertFalse($this->_config->is_enterprise);
	}

	public function testCompany()
	{
		$this->assertEquals('ca', $this->_config->company);
	}

	public function testCampaign()
	{
		$this->assertEquals('ca_t1', $this->_config->campaign);
	}

	public function testEnterprise()
	{
		$this->assertEquals('clk', $this->_config->enterprise);
	}

	public function testBlackboxMode()
	{
		$this->assertEquals(VendorAPI_Blackbox_Config::MODE_BROKER, $this->_config->blackbox_mode);
	}
}

?>