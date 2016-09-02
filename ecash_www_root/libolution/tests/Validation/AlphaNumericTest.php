<?php
class VendorAPI_Validation_AlphaNumericTest extends PHPUnit_Framework_TestCase
{
	public static function isValidDataProvider()
	{
		return array(
			array('', FALSE),
			array('   ', FALSE),
			array(NULL, FALSE),
			array(0, TRUE),
			array('test123', TRUE),
			array('test me', FALSE),
			array('test', TRUE)
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
		$validator = new Validation_AlphaNumeric_1();
		$this->assertEquals($expected, $validator->isValid($value, new ArrayObject()));
	}
	
	public function testisValidWhiteSpace()
	{
		$validator = new Validation_AlphaNumeric_1(TRUE);
		$this->assertTrue($validator->isValid('test me 123', new ArrayObject()));
	}
}
