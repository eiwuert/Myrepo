<?php

/**
 * @package Validation.Filters
 */

/**
 * A filter that validations and normalizes a phone number
 *
 * This is compatible with the legacy normalization in lib/data_validation.2.php
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class Validation_Filters_PhoneNumber_1 implements Validation_IFilter_1
{
	const PHONE_REGEX = '/^([2-9]\d{2}){1}([2-9]\d{2}){1}[0-9]{4}$/';

	protected $field;

	public function __construct($field)
	{
		$this->field = $field;
	}

	public function execute($data, Validation_ValidatorResult_1 $result)
	{
		$value = $data[$this->field];

		// normalize
		$value = str_replace(array('-', ' '), '', $value);

		if (strlen($value) != 10
			||  !preg_match(self::PHONE_REGEX, $value))
		{
			$result->addError(new Validation_ValidatorError_1($this->field, 'Must be a valid phone number'));
			return;
		}

		$result->setData($this->field, $value);
	}
}

?>