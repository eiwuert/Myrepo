<?php

/**
 * @package Validation
 */

/**
 * Allows for normalization and complex validation
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
interface Validation_IFilter_1
{
	/**
	 * Executes the filter, adding errors/normalized data to the validator result
	 * @param ArrayAccess $data
	 * @param Validation_ValidatorResult_1 $result
	 * @return void
	 */
	public function execute($data, Validation_ValidatorResult_1 $result);
}

?>