<?php

/**
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Config extends Blackbox_Config
{
	const MODE_BROKER = 'BROKER';
	const MODE_AGREE = 2;
	const MODE_ECASH_REACT = 'ECASH_REACT';

	/**
	 */
	public function __construct(VendorAPI_Blackbox_DebugConfig $debug = NULL)
	{
		if (!$debug)
		{
			$debug = new VendorAPI_Blackbox_DebugConfig();
		}

		$this->data = array(
			'is_enterprise' => NULL,
			'enterprise' => NULL,
			'company' => NULL,
			'campaign' => NULL,
			'blackbox_mode' => NULL,
			'run_uw' => TRUE,
			'datax_fraud' => FALSE,
			'debug' => $debug,
			'event_log' => null,
			'uw_recur' => TRUE,
			'price_point' => NULL,
			'prev_customer' => TRUE,
			'used_info' => TRUE,
			'verify_rules' => TRUE,
			'verify_paydates' => NULL,
			'site_name' => NULL,
			'persistor' => NULL,
			'call_context' => NULL,
			'is_title_loan' => NULL,
			'run_tribal' => TRUE,
		);
	}
}

?>
