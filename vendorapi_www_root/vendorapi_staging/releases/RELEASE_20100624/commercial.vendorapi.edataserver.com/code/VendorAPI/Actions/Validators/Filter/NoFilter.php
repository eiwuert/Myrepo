<?php
/**
 * Filter implementation that really does no filtering just adds
 * the default value to the normalized data in the result
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */

class VendorAPI_Actions_Validators_Filter_NoFilter implements Validation_IFilter_1
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
		$result->setData($this->field, $data[$this->field]);
	}
}