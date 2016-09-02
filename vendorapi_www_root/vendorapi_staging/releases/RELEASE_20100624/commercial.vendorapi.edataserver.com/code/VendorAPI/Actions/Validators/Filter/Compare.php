<?php
/**
 * Filter to compare 2 fields for Validation 
 * @author raymond lopez <raymond.lopez@sellingsource.com>
 *
 */

class VendorAPI_Actions_Validators_Filter_Compare implements Validation_IFilter_1
{
	protected $field1;
	protected $field2;
	protected $match;

	public function __construct($field1, $field2, $match = TRUE)
	{
		$this->field1 	= $field1;
		$this->field2 	= $field2;
		$this->match 	= $match;
	}

	public function execute($data, Validation_ValidatorResult_1 $result)
	{
		if((@$data[$this->field1] == @$data[$this->field2]) != $this->match)
		{
			$match_str = ($this->match) ? "matches" : "does not match";  
			$meessage_str = "{$this->field1} {$match_str} {$this->field2}.";
			$result->addError(new Validation_ValidatorError_1($this->field1, $meessage_str));				
		}
	}
}