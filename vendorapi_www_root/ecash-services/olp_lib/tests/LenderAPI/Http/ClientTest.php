<?php

/**
 * Test the LenderAPI_Http_Client class.
 *
 * @package LenderAPI
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class LenderAPI_Http_ClientTest extends PHPUnit_Framework_TestCase
{
	/**
	 * A URL not under the control of the local webserver which will drop connections.
	 *
	 * @var string URL
	 */
	protected static $url_refuse = 'http://localhost:8673/unknown_port.php';
	
	/**
	 * A url which hits a port run by a webserver but does not exist.
	 *
	 * @var string URL
	 */
	protected static $url_404 = 'http://localhost/non_existing_url.php';
	
	// -------------------------------------------------------------------------
	
	/**
	 * Tests the default header values for the LenderAPI Http transport for the Http_Post.
	 * 
	 * Essentially, User-Agent and Content-Type should ALWAYS be set, either with
	 * the value passed in OR a default value
	 *
	 * @dataProvider postHeaderDefaultsProvider
	 * @param array $headers List of HTTP headers as strings.
	 * @param array $expected_headers List of headers we expect the client
	 * to set in the curl object it's using. (This sucks, but because there's no
	 * really good way to test that the headers are set irrespective of the 
	 * low level transport layer, this is what we do.)
	 * @param array $unexpected_headers Headers that should NOT show up when posting.
	 * @return void
	 */
	public function testHeaderDefaultsPost($headers, $expected_headers, $unexpected_headers)
	{
		// test that the values we expect to for each header shows up.
		foreach ($expected_headers as $value)
		{
			$mock_client = $this->getMock('LenderAPI_Http_Client', array('setCurlHeaders'));
			
			if ($headers) $mock_client->Set_Headers($headers);
			
			// setCurlHeaders is called with a curl resource, list of headers
			$mock_client->expects($this->once())
				->method('setCurlHeaders')
				->with($this->anything(), $this->contains($value));
			
			$mock_client->Http_Post('http://nowhere.com', '<?xml version="1.0"?><data/>');
		}
		
		// test that the values we DON'T expect to show up don't (values we've overridden or not set)
		foreach ($unexpected_headers as $value)
		{
			$mock_client = $this->getMock('LenderAPI_Http_Client', array('setCurlHeaders'));
			
			if ($headers) $mock_client->Set_Headers($headers);
			
			// setCurlHeaders is called with a curl resource, list of headers
			$mock_client->expects($this->once())
				->method('setCurlHeaders')
				->with($this->anything(), $this->logicalNot($this->contains($value)));
			
			$mock_client->Http_Post('http://nowhere.com', '<?xml version="1.0"?><data/>');
		}
	}
	
	/**
	 * Tests the default header values for the LenderAPI Http transport for the Http_Get version.
	 * 
	 * Essentially, User-Agent and Content-Type should ALWAYS be set, either with
	 * the value passed in OR a default value
	 *
	 * @dataProvider getHeaderDefaultsProvider
	 * @param array $headers List of HTTP headers as strings.
	 * @param array $expected_headers List of headers we expect the client
	 * to set in the curl object it's using. (This sucks, but because there's no
	 * really good way to test that the headers are set irrespective of the 
	 * low level transport layer, this is what we do.)
	 * @param array $unexpected_headers Headers that should NOT show up when posting.
	 * @return void
	 */
	public function testHeaderDefaultsGet($headers, $expected_headers, $unexpected_headers){
		
		// test that the values we expect to for each header shows up.
		foreach ($expected_headers as $value)
		{
			$mock_client = $this->getMock('LenderAPI_Http_Client', array('setCurlHeaders'));
			
			if ($headers) $mock_client->Set_Headers($headers);
			
			// setCurlHeaders is called with a curl resource, list of headers
			$mock_client->expects($this->once())
				->method('setCurlHeaders')
				->with($this->anything(), $this->contains($value));
			
			$mock_client->Http_Get('http://nowhere.com', '<?xml version="1.0"?><data/>');
		}
		
		// test that the values we DON'T expect to show up don't (values we've overridden or not set)
		foreach ($unexpected_headers as $value)
		{
			$mock_client = $this->getMock('LenderAPI_Http_Client', array('setCurlHeaders'));
			
			if ($headers) $mock_client->Set_Headers($headers);
			
			// setCurlHeaders is called with a curl resource, list of headers
			$mock_client->expects($this->once())
				->method('setCurlHeaders')
				->with($this->anything(), $this->logicalNot($this->contains($value)));
			
			$mock_client->Http_Get('http://nowhere.com', '<?xml version="1.0"?><data/>');
		}
	}
	
	/**
	 * Test connection refused errors.
	 *
	 * @param string $method The method on the client to call (e.g. Http_Post)
	 * @param string $url The url to post against.
	 * @param int $error_code The expected error code.
	 * @dataProvider httpErrorsProvider
	 * @return void
	 */
	public function testHttpErrors($method, $url, $error_code)
	{
		$client = new LenderAPI_Http_Client();
		$client->$method($url, array());
		$this->assertEquals(
			$client->getErrorCode(), 
			$error_code,
			sprintf('client error was %s, should have been %s', 
				$client->getErrorCode(), $error_code)
		);
		
		$this->assertNotEquals(
			$client->getErrorMessage(),
			'',
			'Error message was empty!'
		);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * @see LenderAPI_Http_ClientTest::testHeaderDefaults()
	 * @return array
	 */
	public static function postHeaderDefaultsProvider()
	{
		$default_agent = 'User-Agent: SOAP Client';
		$default_type = 'Content-Type: application/xml';
		$bogus_agent = 'User-Agent: SOAPA LA ROPA';
		$bogus_type = 'Content-Type: weeping/clowns';
		
		return array(
			array(array($bogus_agent), array($bogus_agent, $default_type), array($default_agent, $bogus_type)),
			array(array($bogus_type), array($bogus_type, $default_agent), array($default_type, $bogus_agent)),
			array(array(), array($default_type, $default_agent), array($bogus_type, $bogus_agent)),
		);
	}
	
	/**
	 * @see LenderAPI_Http_ClientTest::testHeaderDefaults()
	 * @return array
	 */
	public static function getHeaderDefaultsProvider()
	{
		$default_agent = 'User-Agent: SOAP Client';
		$bogus_agent = 'User-Agent: SOAPA LA ROPA';
		$bogus_type = 'Content-Type: weeping/clowns';
		$bogus_agent = 'User-Agent: SOAPA LA ROPA';
		
		return array(
			array(array($bogus_agent), array($bogus_agent), array($default_agent)),
			array(array($bogus_type), array($default_agent), array($bogus_agent)),
			array(array(), array($default_agent), array($bogus_agent)),
		);
	}
	
	/**
	 * Data provider for the testHttpErrors method.
	 *
	 * @return array
	 */
	public static function httpErrorsProvider()
	{
		return array(
			array('Http_Get', self::$url_refuse, LenderAPI_Http_Client::HTTP_CONNECTION_REFUSED),
			array('Http_Post', self::$url_refuse, LenderAPI_Http_Client::HTTP_CONNECTION_REFUSED),
			array('Http_Get', self::$url_404, LenderAPI_Http_Client::HTTP_ERROR),
			array('Http_Post', self::$url_404, LenderAPI_Http_Client::HTTP_ERROR),
		);
	}
}

?>
