<?php
/**
 * Tests the optional validator decorator.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Validation_OptionalTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test that optional 
	 *
	 */
	public function testIsValid()
	{
		$value = array('stuff' => 'abc');
		
		$validator = new Validation_ArrayValidator_1();
		$validator->addValidator('blue', new Validation_Optional_1(new Validation_Alpha_1()));
		
		$this->assertTrue($validator->validate($value));
	}
	
	/**
	 * Tests that blank strings are considered unset by the optional validator.
	 * 
	 * Added functionality so that blank strings are considered empty for optional validation. This tests that blanks
	 * strings return TRUE, but FALSE and 0 return FALSE.
	 *
	 * @return void
	 */
	public function testIsValidBlankString()
	{
		$validator = new Validation_Optional_1(new Validation_Alpha_1());
		
		$this->assertTrue($validator->isValid('', new ArrayObject()));
		$this->assertFalse($validator->isValid(0, new ArrayObject()));
		$this->assertFalse($validator->isValid(FALSE, new ArrayObject()));
	}
}
