<?php
/**
 * Tests the Regex validator.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Validation_RegexTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that isValid passes with a valid regex.
	 *
	 * @return void
	 */
	public function testIsValidPass()
	{
		$validator = new Validation_Regex_1('/^[a-z]+$/', 'it works!');
		$this->assertTrue($validator->isValid('abcdefghijklmnopqrstuvwxyz', new ArrayObject()));
	}
}
