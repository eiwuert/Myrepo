<?php

class Functional_GetTokens_InvalidApplicationTest extends PHPUnit_Framework_TestCase
{
	protected $_client;

	public function setup()
	{
		$this->_client = getTestClient('clk', 'pcl', 'vendor_api', 'vendor_api');
	}

	public function tearDown()
	{
		$this->_client = null;
	}

	public function testInvalidApplication()
	{
		$response = $this->_client->getTokens(
			1, // application_id
			FALSE // is_preview
		);

		$this->assertFalse($response['outcome']);
		$this->assertArrayHasKey('exception', $response['result']);
		$this->assertEquals('Invalid application', $response['result']['exception']['message']);
	}
}

?>