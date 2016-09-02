<?php

	/**
	 * @package Validation
	 */

	/**
	 * Encapsulates a validation error
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Validation_ValidatorError_1 extends Object_1
	{
		/**
		 * @var string
		 */
		protected $field;

		/**
		 * @var string
		 */
		protected $message;

		/**
		 * @param string $field
		 * @param string $message
		 */
		public function __construct($field, $message)
		{
			$this->field = $field;
			$this->message = $message;
		}

		/**
		 * Returns the field that failed validation
		 *
		 * @return string
		 */
		public function getField()
		{
			return $this->field;
		}

		/**
		 * Returns a message indicating why the field failed validation
		 *
		 * @return string
		 */
		public function getMessage()
		{
			return $this->message;
		}

		/**
		 * Output a string representation of the error
		 *
		 * @return string
		 */
		public function __toString()
		{
			return $this->field.' '.$this->message;
		}
	}

?>
