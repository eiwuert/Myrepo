<?php
/**
 * Validation class for the ViewDocument action.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class VendorAPI_Actions_Validators_ViewDocument extends VendorAPI_Actions_Validators_Base
{
	/**
	 * Initialize the validator conditions.
	 *
	 * @return void
	 */
	public function init()
	{
		$this->addValidator('archive_id', new Validation_Number_1(1));
		$this->addValidator('application_id', new Validation_Number_1(1));
	}
}