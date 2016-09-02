<?php
/**
 * Test case to test the {@see OLPBlackbox_Data} class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_DataTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests the toECashArray() function to see that we get back an array and that the appropriate keys
	 * have been changed.
	 *
	 * @return void
	 */
	public function testToECashArray()
	{
		$data = new OLPBlackbox_Data();
		
		$ecash_array = $data->toECashArray();
		
		$this->assertType('array', $ecash_array);
		$this->assertArrayHasKey('react_application_id', $ecash_array);
		$this->assertArrayHasKey('income_monthly', $ecash_array);
		$this->assertArrayHasKey('income_direct_deposit', $ecash_array);
		$this->assertArrayHasKey('personal_reference', $ecash_array);
	}
}
