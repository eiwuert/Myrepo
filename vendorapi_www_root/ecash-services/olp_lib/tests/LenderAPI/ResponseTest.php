<?php
/**
 * Tests the vendor response
 *
 * @package VendorAPI
 */
class LenderAPI_ResponseTest extends PHPUnit_Framework_TestCase
{
	protected $response;
	
	/**
	 * Prepare objects for use in tests.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->response = new LenderAPI_Response();
	}
	
	/**
	 * Get rid of objects used in tests.
	 *
	 * @return void
	 */
	public function tearDown()
	{
		unset($this->response);
	}
	
	/**
	 * Provide different types of data/xsl which should work in response objects.
	 *
	 * @return array
	 */
	public static function transformProvider()
	{
		// normal XML response and transformation
		$xml_data = <<<EOD
<lead_result><status_id>1</status_id></lead_result>
EOD;
		$xml_xsl = <<<EOD
<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:template match="/lead_result">
<LenderAPI_Response>
<xsl:choose>
<xsl:when test="status_id = 1">
	<success>1</success>
</xsl:when>
<xsl:otherwise>
	<success>0</success>
</xsl:otherwise>
</xsl:choose>
</LenderAPI_Response>
</xsl:template>
</xsl:stylesheet>
EOD;
		// non-xml response and transformation.
		$non_xml_data = '1';
		$non_xml_xsl = <<<EOD
<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:template match="/">
 <LenderAPI_Response>
   <xsl:choose>
     <xsl:when test="non-xml-response = 1">
     	<success>1</success>
     </xsl:when>
     <xsl:otherwise>
     	<success>0</success>
     </xsl:otherwise>
   </xsl:choose>
 </LenderAPI_Response>
</xsl:template>
</xsl:stylesheet>
EOD;
		return array(
			array($xml_data, $xml_xsl),
			array($non_xml_data, $non_xml_xsl),
		);
	}
	
	/**
	 * Test response being assembled from a transformer and data.
	 *
	 * @dataProvider transformProvider
	 * @param string $data The data that would have been sent by a Lender.
	 * @param string $xsl The XSL that would be used to transform said data.
	 * @return void
	 */
	public function testTransform($data, $xsl)
	{
		$transformer = new LenderAPI_XslTransformer();

		$transformer->setXsl($xsl);

		$this->response->transform = $transformer;
		$this->response->dataReceived = $data;
		$this->assertEquals(1, $this->response->Is_Success());
	}
	
	/**
	 * Test the raw construction of a response object from xml and then retrieving
	 * the xml back out of the object.
	 *
	 * @return void
	 */
	public function testFromToXml()
	{
		$xml = <<<EOD
<response>
	<post_time>1228762969</post_time>
	<message>testMessage</message>
	<success>1</success>
	<empty_response>0</empty_response>
	<data_sent>testSent</data_sent>
	<thank_you_content>testThankYou</thank_you_content>
	<next_page>testNextPage</next_page>
	<decision>testDecision</decision>
	<reason>testReason</reason>
	<data_received>testReceived</data_received>
	<timeout_exceeded>0</timeout_exceeded>
	<persistent><offer><zip><price>50</price></zip></offer></persistent>
</response>
EOD;

		$this->response->fromXml($xml);

		$this->assertEquals(trim($this->response->toXml()), '<?xml version="1.0"?>
<LenderAPI_Response><post_time>1228762969</post_time><message>testMessage</message><success>1</success><empty_response>0</empty_response><data_sent>testSent</data_sent><thank_you_content>testThankYou</thank_you_content><next_page>testNextPage</next_page><decision>testDecision</decision><reason>testReason</reason><timeout_exceeded>0</timeout_exceeded><data_received>testReceived</data_received></LenderAPI_Response>');
	}
}
?>
