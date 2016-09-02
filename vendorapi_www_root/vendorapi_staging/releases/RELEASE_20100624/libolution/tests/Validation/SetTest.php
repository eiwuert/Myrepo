<?php
/**
 * Tests the set validator.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Validation_SetTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test that optional 
	 *
	 */
	public function testIsValid()
	{
		$validator = new Validation_Set_1(array('stuff', 'things'));
		
		$this->assertTrue($validator->isValid('stuff', new ArrayObject()));
	}
}
