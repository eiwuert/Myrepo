<?php
/**
 * Tests the String validator.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Validation_StringTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that isValid passes with a valid string length.
	 *
	 * @return void
	 */
	public function testIsValidPass()
	{
		$validator = new Validation_String_1(1, 1);
		$this->assertTrue($validator->isValid('a', new ArrayObject()));
	}
}
