<?php
/**
 * @package Validation
 */

/**
 * SubArray validator.
 *
 * This validator will search an array index that is itself an array and validate the fields benieth that array.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Validation_SubArray_1 extends Validation_ArrayValidator_1 implements Validation_IValidator_1, Validation_IFilter_1
{
	/**
	 * @var String
	 */
	protected $field;

	/**
	 * Construct and set the field to expect a
	 * subarray in

	 * Defaulting to 0 should keep the legacy behavior
	 * @param String $field
	 */
	public function __construct($field = 0)
	{
		$this->field = $field;
	}

	/**
	 * Execute like a filter and set any data on the result
	 * @param array $data
	 * @param Validation_ValidatorResult_1 $result
	 * @return void
	 */
	public function execute($data, Validation_ValidatorResult_1 $result)
	{
		if (is_array($data[$this->field]))
		{
			$new_data = array();
			$errors = FALSE;
			foreach ($data[$this->field] as $d)
			{
				parent::validate($d);
				$filtered_data = $this->result->getData();
				$new_data[] = array_merge($d, $filtered_data);
				if ($this->result->hasErrors())
				{
					$errors = TRUE;
					foreach ($this->result->getErrors() as $error)
					{
						$result->addError($error);
					}
				}
			}
			if ($errors)
			{
				$result->addError(new Validation_ValidatorError_1($this->field, $this->getMessage()));
			}
			$result->setData($this->field, $new_data);

		}
		else
		{
			$result->addError(new Validation_ValidatorError_1($this->field, $this->getMessage()));
		}
	}

	/**
	 * Defined by Validation_IValidator_1
	 *
	 * @param array $value
	 * @param ArrayObject $errors
	 * @return bool
	 */
	public function isValid($value, ArrayObject $errors)
	{
		if (!is_array($value))
		{
			return FALSE;
		}

		if (empty($value))
		{
			return TRUE;
		}

		foreach ($value as $d)
		{
			parent::validate($d);
			foreach ($this->result->getErrors() as $e)
			{
				$errors->append($e);
			}
		}
		return !$this->result->hasErrors();
	}

	/**
	 * Defined in Validation_IValidator_1
	 *
	 * @return string
	 */
	public function getMessage()
	{
		return "must be an array or validation on part failed";
	}
}
