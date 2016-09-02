<?php

	/**
	 * @package Validation
	 */

	require_once 'libolution/Validation/IValidator.1.php';

	/**
	 * Validates that a value is contained (or not contained) in a set
	 * @author Justin Foell <justin.foell@sellingsource.com>
	 */
	class Validation_Set_1 extends Object_1 implements Validation_IValidator_1
	{
		/**
		 * @var bool
		 */
		protected $case_sensitive;

		/**
		 * @var array
		 */
		protected $values;

		/**
		 * @var bool
		 */
		protected $allowed;

		/**
		 * @param array $values
		 * @param bool $allowed If TRUE, $values are restrictions, otherwise they are exclusions
		 * @param bool $case_sensitive
		 */
		public function __construct(array $values, $allowed = TRUE, $case_sensitive = TRUE)
		{
			$this->case_sensitive = $case_sensitive;
			$this->allowed = $allowed;
			if (!$this->case_sensitive)
			{
				$this->values = array_map('strtolower', $values);
			}
			else
			{
				$this->values = $values;
			}
		}

		/**
		 * Returns a message describing the validation requirements
		 *
		 * @return string
		 */
		public function getMessage()
		{
			$not = $this->allowed ? '' : 'not';
			$msg = "must {$not} be contained in set: \"" . join(',', $this->values) . '"';
			if ($this->case_sensitive)
				$msg .= ' (case sensitive)';

			return $msg;
		}

		/**
		 * Validates the provided value against the set
		 *
		 * @param string $value
		 * @return bool
		 */
		public function isValid($value)
		{
			if (!$this->case_sensitive)
			{
				$value = strtolower($value);
			}

			$result = in_array($value, $this->values);
			if ($this->allowed)
				return $result;

			return !$result;
		}
	}

?>
