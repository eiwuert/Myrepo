<?php
/**
 * Base validator class for the Vendor API.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class VendorAPI_Actions_Validators_Base extends Validation_ArrayValidator_1
{
	public function __construct()
	{
		$this->init();
	}
	
	public function getFilteredData()
	{
		return $this->result->getData();
	}
}
