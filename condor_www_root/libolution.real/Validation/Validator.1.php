<?php

	/**
	 * @package Validation
	 */

	require_once 'libolution/Validation/ValidatorError.1.php';

	/**
	 * Base class for a collection of validators that will get run against a request
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	abstract class Validation_Validator_1 extends Object_1
	{
		/**
		 * @var array
		 */
		protected $filters = array();

		/**
		 * @var array
		 */
		protected $errors = array();

		/**
		 * @var bool
		 */
		protected $is_valid;

		/**
		 * Extracts the value for a field from the request
		 *
		 * @param mixed $request
		 * @param string $field
		 */
		abstract protected function getFieldValue($request, $field);

		/**
		 * Adds a validator object for a specific field
		 * @param string $field Field name
		 * @param Validation_IValidator_1 $filter
		 * @return void
		 */
		public function addValidator($field, Validation_IValidator_1 $filter)
		{
			if (isset($this->filters[$field]))
			{
				$this->filters[$field][] = $filter;
			}
			else
			{
				$this->filters[$field] = array($filter);
			}
		}

		/**
		 * Returns an array of ValidatorError objects
		 * @return array
		 */
		public function getErrors()
		{
			return $this->errors;
		}

		/**
		 * Runs all current validation rules against a field collection
		 * @param mixed $request
		 * @return bool
		 */
		public function validate($request)
		{
			$this->is_valid = TRUE;
			$this->errors = array();

			foreach ($this->filters as $field => $filters)
			{
				foreach ($filters as $filter)
				{
					if ($filter->isValid($this->getFieldValue($request, $field)) !== TRUE)
					{
						$this->errors[] = new Validation_ValidatorError_1($field, $filter->getMessage());
						$this->is_valid = FALSE;
					}
				}
			}

			return $this->is_valid;
		}
	}

?>
