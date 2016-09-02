<?php

/**
 * @package Validation
 */

/**
 * Validation result
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class Validation_ValidatorResult_1
{
	/**
	 * @var array
	 */
	protected $errors = array();

	/**
	 * @var array
	 */
	protected $data = array();

	/**
	 * Adds a validation error
	 * @param Validation_ValidatorError_1 $err
	 * @return void
	 */
	public function addError(Validation_ValidatorError_1 $err)
	{
		$this->errors[] = $err;
	}

	/**
	 * Sets normalized data
	 * @param string $field
	 * @param string $value
	 * @return void
	 */
	public function setData($field, $value)
	{
		$this->data[$field] = $value;
	}

	/**
	 * Whether this result contains normalized data
	 * @return bool
	 */
	public function hasData()
	{
		return !empty($this->data);
	}

	/**
	 * Returns all normalized data
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Returns all errors
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Whether this result contains errors
	 * @return bool
	 */
	public function hasErrors()
	{
		return !empty($this->errors);
	}

	/**
	 * Indicates if the result contains the given field. This returns
	 * true even if the value of the field is null.
	 * @param string $field Field name
	 * @return boolean
	 */
	public function hasDataField($field)
	{
		return isset($this->data[$field]) || array_key_exists($field, $this->data);
	}


	/**
	 * Return a single data field in this result
	 * @param string $field
	 * @return string
	 */
	public function getDataField($field)
	{
		if (!isset($this->data[$field]))
		{
			return NULL;
		}
		return $this->data[$field];
	}
}

?>
