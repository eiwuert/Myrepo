<?php

	/**
	 * @package Validation
	 */

	require_once 'libolution/Validation/IValidator.1.php';

	/**
	 * Validates that a string value is between a maximum and minimum length
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Validation_String_1 extends Object_1 implements Validation_IValidator_1
	{
		/**
		 * @var int
		 */
		protected $min_length;

		/**
		 * @var int
		 */
		protected $max_length;

		/**
		 * @param int $min
		 * @param int $max
		 */
		public function __construct($min = NULL, $max = NULL)
		{
			$this->min_length = $min;
			$this->max_length = $max;
		}

		/**
		 * Returns the minimum required length
		 *
		 * @return int
		 */
		public function getMinLength()
		{
			return $this->min_length;
		}

		/**
		 * Returns the maximum allowed length
		 *
		 * @return int
		 */
		public function getMaxLength()
		{
			return $this->max_length;
		}

		/**
		 * Gets a message describing the requirements
		 *
		 * @return string
		 */
		public function getMessage()
		{

			if ($this->min_length && $this->max_length)
			{
				return "must be between {$this->min_length} and {$this->max_length} characters";
			}
			elseif ($this->min_length)
			{
				return "must be at least {$this->min_length} characters";
			}
			elseif ($this->max_length)
			{
				return "must be no more than {$this->max_length} characters";
			}

		}

		/**
		 * Validates a value against the min and max lengths
		 *
		 * @param string $value
		 * @return bool
		 */
		public function isValid($value)
		{
			$l = strlen($value);

			return (($this->min_length === NULL || $l >= $this->min_length)
				&& ($this->max_length === NULL || $l <= $this->max_length));
		}
	}

?>
