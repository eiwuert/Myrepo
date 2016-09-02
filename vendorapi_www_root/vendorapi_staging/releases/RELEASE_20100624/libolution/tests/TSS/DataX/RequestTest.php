<?php
/**
 * Test case for the TSS_DataX_Request class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class TSS_DataX_RequestTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that we get an XML file with the required fields.
	 *
	 * @return void
	 */
	public function testTransformData()
	{
		$xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/request-transformData.xml');
		
		$data = array(
			'application_id'	=> 1234567,
			'track_id'			=> 'abc123',
			'name_first'		=> 'John',
			'name_last'			=> 'Doe',
			'name_middle'		=> 'C'
		);
		$request = new TSS_DataX_Request('license', 'password', 'my_call');
		
		$this->assertEquals($xml, simplexml_load_string($request->transformData($data)));
	}
}
