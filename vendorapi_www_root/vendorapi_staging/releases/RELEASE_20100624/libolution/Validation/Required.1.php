<?php

/**
 * @package Validation
 */

/**
 * Validates that a field exists
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class Validation_Required_1 implements Validation_IValidator_1
{
	public function getMessage()
	{
		return 'is required';
	}

	public function isValid($value, ArrayObject $errors)
	{
		return ($value !== NULL);
	}
}

?>