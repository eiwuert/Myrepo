<?php
/**
 * Tests the Date validator.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Validation_DateTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that isValid passes with a valid date.
	 *
	 * @return void
	 */
	public function testIsValidPass()
	{
		$validator = new Validation_Date_1();
		$this->assertTrue($validator->isValid('2008-02-13', new ArrayObject()));
	}
}
