<?php
/**
 * @package Validation
 */

/**
 * Ensures the field is not empty.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Validation_NotEmpty_1 implements Validation_IValidator_1
{
	/**
	 * Defined in Validation_IValidator_1.
	 *
	 * @param mixed $value
	 * @param ArrayObject $errors
	 * @return bool
	 */
	public function isValid($value, ArrayObject $errors)
	{
		if (is_string($value))
		{
			return ($value != '' && !preg_match('/^\s+$/s', $value));
		}
		return !empty($value);
	}

	/**
	 * Implements getMessage defined in Validation_IValidator_1.
	 *
	 * @return string
	 */
	public function getMessage()
	{
		return "must not be empty";
	}
}
