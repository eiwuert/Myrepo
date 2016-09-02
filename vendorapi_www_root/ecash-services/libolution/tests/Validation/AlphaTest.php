<?php
class VendorAPI_Validation_AlphaTest extends PHPUnit_Framework_TestCase
{
	public static function isValidDataProvider()
	{
		return array(
			array('', FALSE),
			array('   ', FALSE),
			array(NULL, FALSE),
			array(0, FALSE),
			array('test123', FALSE),
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
		$validator = new Validation_Alpha_1();
		$this->assertEquals($expected, $validator->isValid($value, new ArrayObject()));
	}
	
	public function testisValidWhiteSpace()
	{
		$validator = new Validation_Alpha_1(TRUE);
		$this->assertTrue($validator->isValid('test me', new ArrayObject()));
	}
}
