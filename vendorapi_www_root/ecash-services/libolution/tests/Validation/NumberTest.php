<?php
/**
 * Tests the Number validator.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Validation_NumberTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that isValid passes with a valid number.
	 *
	 * @return void
	 */
	public function testIsValidPass()
	{
		$validator = new Validation_Number_1(1, 1);
		$this->assertTrue($validator->isValid('1', new ArrayObject()));
	}
}
