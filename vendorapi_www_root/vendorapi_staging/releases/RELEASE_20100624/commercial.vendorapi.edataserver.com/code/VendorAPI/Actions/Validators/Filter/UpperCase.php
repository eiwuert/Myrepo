<?php
/**
 * Filter to upper case a field
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */

class VendorAPI_Actions_Validators_Filter_UpperCase implements Validation_IFilter_1
{
	/**
	 * @var string
	 */
	protected $field;

	/**
	 * 
	 * @param string $field
	 */
	public function __construct($field)
	{
		$this->field = $field;
	}

	/**
	 * (non-PHPdoc)
	 * @see Validation/Validation_IFilter_1#execute($data, $result)
	 */
	public function execute($data, Validation_ValidatorResult_1 $result)
	{
		if (!empty($data[$this->field]))
		{
			
			$result->setData($this->field, strtoupper($data[$this->field]));
		}
	}
}