<?php

	/**
	 * @package Validation
	 */

	require_once 'libolution/Validation/Validator.1.php';

	/**
	 * Manages a collection of Validators and runs them on a request object/array
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Validation_ArrayValidator_1 extends Validation_Validator_1
	{
		/**
		 * Extracts the value for a field from the request array
		 *
		 * @param array $request
		 * @param string $field
		 * @return mixed
		 */
		protected function getFieldValue($request, $field)
		{
			return @$request[$field];
		}
	}

?>
