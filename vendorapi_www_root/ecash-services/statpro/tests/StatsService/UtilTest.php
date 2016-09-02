<?php
/**
 * Unit tests for StatsService_Util
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class StatsService_UtilTest extends PHPUnit_Framework_TestCase {
	/**
	 * Verify that correct customer is returned 
	 */
	public function testGetCustomerFromBucket() {
		$this->assertEquals(
			"cust",
			StatsService_Util::getCustomerFromBucket("spc_cust_live"));
	}
}