<?php
/**
 * Test the transformer object.
 *
 * @author Dan Ostrowski
 * @package VendorAPI
 */
class LenderAPI_XslTransformerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Check to see if the XSLT extensions are available.
	 * @return void
	 */
	public function setUp()
	{
		if (!extension_loaded('libxml')
			|| !extension_loaded('xsl')
			|| !extension_loaded('xml'))
		{
			$this->markTestSkipped('missing essential modules');
		}
	}
	/**
	 * Add a data source which is translated into XML and then transformed.
	 * 
	 * Normally, a data source will be something like a wrapper around 
	 * a Blackbox_Data object.
	 * 
	 * @return void
	 */
	public function testDataSourceTransform()
	{
		$transformer = new LenderAPI_XslTransformer();
		
		$data_source = new ArrayObject();
		$data_source['address'] = '123 Brown St.';
		$data_source['name_first'] = 'Joe';
		
		$transformer->addDataSource($data_source, 'application');
		
		$transformer->setXsl(
			file_get_contents(dirname(__FILE__) . '/_fixtures/Vendor.xsl')
		);
		
		$doc = new DOMDocument();
		
		$this->assertTrue($doc->loadXML($transformer->transform()));
		$xpath = new DOMXPath($doc);
		
		$this->assertEquals('JOE', $xpath->evaluate('//app/name')->item(0)->nodeValue);
	}
	
	/**
	 * The LenderAPI_XslTransformer's transform() method should accept a string
	 * or DOMDocument.
	 *
	 * @return array
	 */
	public static function xmlTransformDataProvider()
	{
		$string_xml = file_get_contents(
			dirname(__FILE__) . '/_fixtures/BlackboxData.xml'
		);
		$dom_xml = new DOMDocument();
		$dom_xml->loadXML($string_xml); 
		return array(
			array($dom_xml),
			array($string_xml),
		);
	}
	
	/**
	 * Test trasnforming raw XML with the XslTransformer.
	 * 
	 * @dataProvider xmlTransformDataProvider
	 * @return void
	 */
	public function testXMLTransform($xml)
	{
		$transformer = new LenderAPI_XslTransformer();
		
		$transformer->setXsl(
			file_get_contents(dirname(__FILE__) . '/_fixtures/Vendor.xsl')
		);
		
		$doc = new DOMDocument();
		$doc->loadXML(
			$transformer->transform($xml)
		);
		$xpath = new DOMXPath($doc);
		
		$this->assertEquals('BOB', $xpath->evaluate('//app/name')->item(0)->nodeValue);
	}
	
	/**
	 * Lacking a stylesheet, the transformer should translate data sources into XML.
	 * 
	 * @return void
	 */
	public function testNoStylesheetTransform()
	{
		$transformer = new LenderAPI_XslTransformer();
		
		$data_source = new ArrayObject();
		$data_source['address'] = '123 Brown St.';
		$data_source['name_first'] = 'Joe';
		
		$transformer->addDataSource($data_source, 'application');
		
		$doc = new DOMDocument();
		$doc->loadXML($transformer->transform());
		$xpath = new DOMXPath($doc);
		
		$this->assertEquals('Joe', $xpath->evaluate('//data/application/name_first')->item(0)->nodeValue);
	}
	
	public function testSpecialCharsTransform()
	{
		$transformer = new LenderAPI_XslTransformer();
		
		$data_source = new ArrayObject();
		$data_source['address'] = '123 Brown St.';
		$data_source['employer'] = 'Joe & Smiths';
		
		$transformer->addDataSource($data_source, 'application');
		
		$doc = new DOMDocument();
		$doc->loadXML($transformer->transform());
		$xpath = new DOMXPath($doc);
		
		$this->assertEquals('Joe & Smiths', $xpath->evaluate('//data/application/employer')->item(0)->nodeValue);
	}
}
?>
