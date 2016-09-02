<?php
class VendorAPI_Validation_NotEmptyTest extends PHPUnit_Framework_TestCase
{
	public static function isValidDataProvider()
	{
		return array(
			array('', FALSE),
			array('   ', FALSE),
			array(NULL, FALSE),
			array(0, FALSE),
			array('test', TRUE),
			array(1, TRUE)
		);
	}
	
	/**
	 * Tests that isValid returns correct.
	 *
	 * @dataProvider isValidDataProvider
	 * @param mixed $value
	 * @param bool $expected
	 * @return void
	 */
	public function testisValid($value, $expected)
	{
		$validator = new Validation_NotEmpty_1();
		$this->assertEquals($expected, $validator->isValid($value, new ArrayObject()));
	}
}
