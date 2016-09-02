<?php
class VendorAPI_Actions_Validators_Filter_BankAccount implements Validation_IFilter_1
{
	protected $field;

	public function __construct($field)
	{
		$this->field = $field;
	}

	public function execute($data, Validation_ValidatorResult_1 $result)
	{
		$result->setData($this->field, preg_replace('/[^\d]/','', $data[$this->field]));
	}
}