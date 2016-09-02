<?php

/**
 * Tests an iterator that builds itself from a traversable representation of
 * rows from the target_data table.
 * 
 * @package LenderAPI
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class LenderAPI_HttpHeadersIteratorTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * Data provider for {@see testConstructor()}.
	 * @return array
	 */
	public static function constructorTestDataProvider()
	{
		$valid_array = array(
			'vendor_api_header_name_1' => 'SoapAction',
			'vendor_api_header_value_1' => 'http://url.at.somewhere.com?#go',
			'useless_key_entry' => 'but harmless!',
		);
		
		$invalid_array = array(
			'not the right' => 'key value',
			'again_not_valid' => 'key',
		);
		
		$valid_object = new ArrayObject(array(
			'vendor_api_header_name_2' => 'Content-Type',
			'extra_key' => 'not-needed',
		));
		return array(
			array($valid_array, FALSE),
			array($invalid_array, TRUE),
			array($valid_object, FALSE),
		);
	}
	
	/**
	 * Tests that valid and invalid construction arguments work with this
	 * object.
	 * @dataProvider constructorTestDataProvider
	 * @param array|Traversable $data A representation of target_data rows.
	 * @param bool $exception_expected Whether to expect an InvalidArgumentException
	 * from the constructor of the tested object.
	 * @return void
	 */
	public function testConstructor($data, $exception_expected)
	{
		if ($exception_expected)
		{
			$this->setExpectedException('InvalidArgumentException');
		}
		
		new LenderAPI_HttpHeadersIterator($data, LenderAPI_Generic_Client::POST_TYPE_STANDARD);
	}
}

?>
