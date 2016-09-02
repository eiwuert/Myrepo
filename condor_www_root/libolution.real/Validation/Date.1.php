<?php

	/**
	 * @package Validation
	 */

	require_once 'libolution/Validation/IValidator.1.php';

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
		 * @return bool
		 */
		public function isValid($value)
		{
			return (strtotime($value) !== FALSE);
		}

	}

?>
