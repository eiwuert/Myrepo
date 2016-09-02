<?php
/**
 * 
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class VendorAPI_Actions_Validators_SubmitPage extends VendorAPI_Actions_Validators_Base implements VendorAPI_IValidator
{
	/**
	 * Initializes the Post action's required data validator.
	 *
	 * @return void
	 */
	public function init()
	{
		for ($i = 1;$i < 5;$i++) 
		{
			$this->addFilter(
				new VendorAPI_Actions_Validators_Filter_Optional(
					'ref_0'.$i.'_phone_home',
					new VendorAPI_Actions_Validators_Filter_PhoneNumber('ref_0'.$i.'_phone_home')
				)
			);
		}
	}
}
