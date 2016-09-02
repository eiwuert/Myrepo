<?php
/**
 * Utility methods for StatsService
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class StatsService_Util {
	
	/**
	 * Determines the customer name from the given Statpro bucket name.
	 * @param string $bucket Bucket name (eg., spc_clk_test)
	 * @return string Customer name (eg. clk)
	 */
	public static function getCustomerFromBucket($bucket) {
		$parts = split("_", $bucket);
		return $parts[1];
	}
}