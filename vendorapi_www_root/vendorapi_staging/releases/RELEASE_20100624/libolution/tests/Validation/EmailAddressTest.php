<?php
/**
 * Tests the EmailAddress validator.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Validation_EmailAddressTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that isValid passes with a valid email address.
	 *
	 * @return void
	 */
	public function testIsValidPass()
	{
		$validator = new Validation_EmailAddress_1();
		$this->assertTrue($validator->isValid('brian.feaver@sellingsource.com', new ArrayObject()));
	}
}
