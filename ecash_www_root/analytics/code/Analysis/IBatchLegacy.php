<?php

/**
 * Provides an extended interface to support legacy batch processing.
 */
interface Analysis_IBatchLegacy extends Analysis_IBatch
{
	/**
	 * @param string $mode
	 * @param string $company
	 * @return DB_IDatabaseConfig_1
	 */
	public static function getLegacyDb($company, $mode);
}

?>
