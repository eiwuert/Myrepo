<?php

/**
 * @package Validation
 */

/**
 * Decorator that makes a validation rule optional
 *
 * If the field exists (not NULL), the validation rule is run,
 * otherwise, the field is considered valid.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class Validation_Optional_1 implements Validation_IValidator_1
{
	/**
	 * @var Validation_IValidator_1
	 */
	protected $validator;

	/**
	 * @param Validation_IValidator_1 $validator
	 */
	public function __construct(Validation_IValidator_1 $validator)
	{
		$this->validator = $validator;
	}

	/**
	 * Describes the validation requirements
	 *
	 * @see Validation/Validation_IValidator_1#getMessage()
	 * @return string
	 */
	public function getMessage()
	{
		return 'if present, '.$this->validator->getMessage();
	}

	/**
	 * Validates the given value
	 *
	 * If the value does not exist, returns TRUE. Otherwise,
	 * runs the interior validator.
	 *
	 * @param mixed $value
	 * @param ArrayObject $errors
	 * @return bool
	 */
	public function isValid($value, ArrayObject $errors)
	{
		if ($value === NULL || $value === '')
		{
			return TRUE;
		}

		return $this->validator->isValid($value, $errors);
	}
}

?>