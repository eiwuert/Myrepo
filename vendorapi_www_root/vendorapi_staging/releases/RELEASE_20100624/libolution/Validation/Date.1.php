<?php

	/**
	 * @package Validation
	 */

	/**
	 * Validates a date.
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Validation_Date_1 extends Object_1 implements Validation_IValidator_1
	{
		/**
		 * Returns a message describing the validation requirements
		 *
		 * @return sting
		 */
		public function getMessage()
		{
			return 'must be a valid date';
		}

		/**
		 * Validates the provided value
		 *
		 * @param mixed $value
		 * @param ArrayObject $errors
		 * @return bool
		 */
		public function isValid($value, ArrayObject $errors)
		{
			return (strtotime($value) !== FALSE);
		}

	}

?>
