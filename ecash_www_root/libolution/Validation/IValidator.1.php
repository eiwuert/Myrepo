<?php

	/**
	 * @package Validation
	 */

	/**
	 * Validates a value
	 */
	interface Validation_IValidator_1
	{
		/**
		 * Returns whether $value is valid
		 * @param mixed $value
		 * @param ArrayObject $errors
		 * @return bool
		 */
		public function isValid($value, ArrayObject $errors);

		/**
		 * Returns a message describing a valid value
		 * @return string
		 */
		public function getMessage();
	}

?>
