<?php

/**
 * @package Validation
 */

/**
 * Adapts the old IValidator interface to the new IFilter interface
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class Validation_ValidatorAdapter_1 implements Validation_IFilter_1
{
	/**
	 * @param string $field
	 * @param Validation_IValidator_1 $filter
	 */
	public function __construct($field, Validation_IValidator_1 $filter)
	{
		$this->field = $field;
		$this->filter = $filter;
	}

	/**
	 * Returns the field name
	 * @return string
	 */
	public function getField()
	{
		return $this->field;
	}

	/**
	 * Executes the internal validator
	 * @param ArrayAccess $data
	 * @param Validation_ValidatorResult_1 $result
	 * @return void
	 */
	public function execute($data, Validation_ValidatorResult_1 $result)
	{
		$value = $data[$this->field];
		$errors = new ArrayObject();

		if (!$this->filter->isValid($value, $errors))
		{
			foreach ($errors as $e)
			{
				$result->addError($e);
			}

			$error = new Validation_ValidatorError_1(
				$this->field,
				$this->filter->getMessage()
			);
			$result->addError($error);
		}
	}
}

?>