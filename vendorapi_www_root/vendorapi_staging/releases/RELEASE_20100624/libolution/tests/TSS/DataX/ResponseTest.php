<?php
/**
 * Tests the abstract TSS_DataX_Response class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class TSS_DataX_ResponseTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that we get errors generated if there are errors in the XML response.
	 *
	 * @return void
	 */
	public function testParseXmlError()
	{
		$xml = <<<XML
<DataxResponse>
	<Response>
		<ErrorCode>E1</ErrorCode>
		<ErrorMsg>Foobar</ErrorMsg>
	</Response>
</DataxResponse>
XML;
		$response = new Test_Response();
		
		$this->assertFalse($response->parseXML($xml));
		$this->assertEquals('E1', $response->getErrorCode());
		$this->assertEquals('Foobar', $response->getErrorMsg());
	}
	
	/**
	 * Tests that if there are no errors, parseXml() returns TRUE.
	 *
	 * @return void
	 */
	public function testParseXmlPass()
	{
		$xml = <<<XML
<DataxResponse>
	<Response>
	</Response>
</DataxResponse>
XML;
		$response = new Test_Response();
		
		$this->assertTrue($response->parseXML($xml));
	}
}

/**
 * Test response class
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Test_Response extends TSS_DataX_Response
{
	/**
	 * Impemented for the interface
	 *
	 * @return bool
	 */
	public function isValid()
	{
		return TRUE;
	}
}
