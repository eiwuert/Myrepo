<?php

/**
 * @package Validation
 */

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
	 * @var bool
	 */
	protected $is_valid;

	/**
	 * @var Validation_ValidatorResult_1
	 */
	protected $result;

	/**
	 * Extracts the value for a field from the request
	 *
	 * @param mixed $request
	 * @param string $field
	 */
	abstract protected function getFieldValue($request, $field);

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->result = new Validation_ValidatorResult_1();
	}

	/**
	 * Adds a new filter to the validator
	 * @param Validation_IFilter_1 $filter
	 * @return void
	 */
	public function addFilter(Validation_IFilter_1 $filter)
	{
		$this->filters[] = $filter;
	}

	/**
	 * Adds a validator object for a specific field
	 * @param string $field Field name
	 * @param Validation_IValidator_1 $validator
	 * @return void
	 */
	public function addValidator($field, Validation_IValidator_1 $validator)
	{
		$filter = new Validation_ValidatorAdapter_1($field, $validator);

		// cluster by field to keep the behavior the same
		if (!isset($this->filters[$field]))
		{
			$this->filters[$field] = array();
		}
		$this->filters[$field][] = $filter;
	}

	/**
	 * Adds an array of filter validators for a specified field
	 *
	 * @param string $field
	 * @param array $filter_list
	 * @return void
	 */
	public function addValidators($field, array $filter_list)
	{
		foreach ($filter_list as $filter)
		{
			$this->addValidator($field, $filter);
		}
	}

	/**
	 * Returns an array of ValidatorError objects
	 *
	 * @return array
	 */
	public function getErrors()
	{
		return $this->result->getErrors();
	}

	/**
	 * Runs all current validation rules against a field collection
	 * @param mixed $request
	 * @return bool
	 */
	public function validate($request)
	{
		$result = new Validation_ValidatorResult_1();

		foreach ($this->filters as $field=>$filter)
		{
			if (is_array($filter))
			{
				foreach ($filter as $f)
				{
					$data = array(
						$field => $this->getValueForFilter($result, $request, $field)
					);
					$f->execute($data, $result);
				}
			}
			else
			{
				$filter->execute($request, $result);
			}
		}

		$this->result = $result;
		$this->is_valid = !$result->hasErrors();
		return $this->is_valid;
	}

	/**
	 * Grab a value either from the result if it's already been set
	 * in there by a filter, or from the request if not
	 * @param Validation_ValidatorResult_1 $result
	 * @param mixed $request
	 * @param string $field
	 * @return string
	 */
	public function getValueForFilter(Validation_ValidatorResult_1 $result, $request, $field)
	{
		$value = $result->getDataField($field);
		if (empty($value))
		{
			$value = $this->getFieldValue($request, $field);
		}
		return $value;
	}

}

?>
