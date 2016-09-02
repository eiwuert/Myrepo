<?php

	/**
	 * @package Validation
	 */

	require_once 'libolution/Validation/IValidator.1.php';

	/**
	 * Validates that a value is numeric, and -- optionally -- between
	 * a minimum and/or maximum value.
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Validation_Number_1 extends Object_1 implements Validation_IValidator_1
	{
		/**
		 * @var int
		 */
		protected $min;

		/**
		 * @var int
		 */
		protected $max;

		public function __construct($min = NULL, $max = NULL)
		{
			$this->min = $min;
			$this->max = $max;
		}

		/**
		 * Returns the minimum allowed value
		 *
		 * @return int
		 */
		public function getMin()
		{
			return $this->min;
		}

		/**
		 * Returns the maximum allow value
		 *
		 * @return int
		 */
		public function getMax()
		{
			return $this->max;
		}

		/**
		 * Returns a message describing the validation requirements
		 *
		 * @return string
		 */
		public function getMessage()
		{

			if ($this->min && $this->max)
			{
				return 'must be between '.$this->min.' and '.$this->max;
			}
			elseif ($this->min)
			{
				return 'must be at least '.$this->min;
			}
			elseif ($this->max)
			{
				return 'must be less than '.$this->max;
			}

			return 'must be numeric';

		}

		/**
		 * Validates the provided value against the min and max
		 *
		 * @param mixed $value
		 * @return bool
		 */
		public function isValid($value)
		{
			return (is_numeric($value)
				&& ($this->min === NULL || ($value >= $this->min))
				&& ($this->max === NULL || ($value <= $this->max)));
		}

	}

?>
