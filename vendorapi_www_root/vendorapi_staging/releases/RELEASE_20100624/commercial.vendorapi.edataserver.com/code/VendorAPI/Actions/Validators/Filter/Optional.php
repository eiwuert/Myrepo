<?php
class VendorAPI_Actions_Validators_Filter_Optional implements Validation_IFilter_1
{
	protected $field;
	protected $filter;
	
	public function __construct($field, Validation_IFilter_1 $filter)
	{
		$this->field = $field;
		$this->filter = $filter;	
	}
	
	/**
	 * Execute like a filter and set any data on the result
	 * @param array $data
	 * @param Validation_ValidatorResult_1 $result
	 * @return void
	 */
	public function execute($data, Validation_ValidatorResult_1 $result)
	{
		if (isset($data[$this->field]))
		{
			$this->filter->execute($data, $result);
		}
	}
}