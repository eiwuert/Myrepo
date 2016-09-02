<?php
/**
* Filter to repalce ampersand by "And" case a field
*/

class VendorAPI_Actions_Validators_Filter_Ampersand implements Validation_IFilter_1
{
	
	protected $field;

	public function __construct($field)
	{
		$this->field = $field;
	}

	public function execute($data, Validation_ValidatorResult_1 $result)
	{
		if (!empty($data[$this->field]))
		{
	        
			$result->setData($this->field, str_replace("&", "And", $data[$this->field]));
		}
	}
}
