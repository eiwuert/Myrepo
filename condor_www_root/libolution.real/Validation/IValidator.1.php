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
		 * @return bool
		 */
		public function isValid($value);

		/**
		 * Returns a message describing a valid value
		 * @return string
		 */
		public function getMessage();
	}

?>
