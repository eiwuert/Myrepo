<?php

	/**
	 * @package Validation
	 */

	/**
	 * Manages a collection of Validators and runs them on a request object/array
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Validation_ObjectValidator_1 extends Validation_Validator_1
	{

		/**
		 * Returns the value for a specific field from the field collection
		 * @param object $request Field collection
		 * @param string $field Field name
		 * @return mixed;
		 */
		protected function getFieldValue($request, $field)
		{
			return isset($request->{$field}) ? $request->{$field} : NULL;
		}

	}

?>
