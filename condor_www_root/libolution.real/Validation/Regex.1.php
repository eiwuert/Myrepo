<?php

	/**
	 * @package Validation
	 */

	require_once 'libolution/Validation/IValidator.1.php';

	/**
	 * Validates a string against a regular expression match
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Validation_Regex_1 extends Object_1 implements Validation_IValidator_1
	{
		/**
		 * @var string
		 */
		protected $regex;

		/**
		 * @var string
		 */
		protected $message;

		/**
		 * On failure, $message will be shown
		 * @param string $regex
		 * @param string $message
		 */
		public function __construct($regex, $message)
		{
			$this->regex = $regex;
			$this->message = $message;
		}

		/**
		 * Returns the regex that will be used for validation
		 *
		 * @return string
		 */
		public function getRegex()
		{
			return $this->regex;
		}

		/**
		 * Returns a message describing the validation requirements
		 *
		 * @return unknown
		 */
		public function getMessage()
		{
			return $this->message;
		}

		/**
		 * Validates the provided value against the regex
		 *
		 * @param string $value
		 * @return bool
		 */
		public function isValid($value)
		{
			return (preg_match($this->regex, $value) > 0);
		}

	}

?>
