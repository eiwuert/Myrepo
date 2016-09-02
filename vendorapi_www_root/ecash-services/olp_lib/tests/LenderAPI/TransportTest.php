<?php

/**
 * Tests the transport object
 *
 * @package VendorAPI
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class LenderAPI_TransportTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test error responses from http agent being pushed into the resposne properly.
	 *
	 * @dataProvider errorsInResponseProvider
	 * @param string $url The URL to post to, will actually be posted to.
	 * @param string $method The method to post with, like "GET" or "POST"
	 * @param string $data The XML data to send.
	 * @param string $decision The vendor decision we expect from the response
	 * object.
	 * @param int $error_code The error code we expect from the agent.
	 * @param string $error_response The error message from the agent.
	 * @return void
	 */
	public function testErrorsInResponse(
		$url, $method, $data, $decision, $error_code, $error_response
	)
	{
		$agent = $this->getMock(
			'LenderAPI_Http_Client', array('getErrorCode', 'getErrorMessage')
		);
		$agent->expects($this->any())
			->method('getErrorCode')
			->will($this->returnValue($error_code));
		$agent->expects($this->any())
			->method('getErrorMessage')
			->will($this->returnValue($error_response));
		
		$response = new LenderAPI_Response();
		$transport = new LenderAPI_Transport($url, $method, NULL, $response, NULL, $agent);
		$transport->send($data);
		$this->assertEquals(
			$response->getDecision(), $decision,
			"decision should have been $decision but was " . $response->getDecision()
		);
		
		$this->assertTrue(
			stristr($response->getReason(), strval($error_code)) !== FALSE,
			'"' . $response->getReason() . "\" did not contain $error_code."
		);
		$this->assertTrue(
			stristr($response->getReason(), strval($error_response)) !== FALSE,
			'"' . $response->getReason() . "\" did not contain $error_response."
		);
	}
	
	/**
	 * Provide data for the testErrorsInResponse() method.
	 *
	 * @return array {@see testErrorsInResponse()} for details.
	 */
	public static function errorsInResponseProvider()
	{
		// url doesn't really matter since we're providing the error codes
		$url = 'http://localhost/non_existant_url.php';
		$fake_data = '<?xml version="1.0"?><data><fake /></data>';
		
		return array(
			array($url, 'POST', $fake_data, 'ERROR', 22, 'Object not found!'),
			array($url, 'GET', $fake_data, 'ERROR', 7, 'No response!'),
		);
	}
}
?>
