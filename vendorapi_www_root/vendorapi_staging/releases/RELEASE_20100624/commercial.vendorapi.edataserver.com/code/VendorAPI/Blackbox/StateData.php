<?php

/** Class for holding state information for VendorAPI Blackbox.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class VendorAPI_Blackbox_StateData extends Blackbox_StateData
{
	/** Sets up the mutable keys.
	 *
	 * @param array $data Data to initalize with.
	 */
	function __construct($data = NULL)
	{
		$this->mutable_keys = array(
			'is_react',
			'react_application_id',
			'customer_history',
			'loan_actions',
			'uw_provider',
			'uw_track_hash',
			'uw_call_history',
			'uw_decision',
			'lead_cost',
			'failure_reason',
			'loan_amount_decision',
			'adverse_action',
			'qualified_loan_amount',
			'fail_type',
			'auto_fund'
		);
		parent::__construct($data);
	}
}

?>
