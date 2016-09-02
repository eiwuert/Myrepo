<?php
/**
 * Definition of the OLPBlackbox_TargetStateData class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */

/**
 * Class for holding state information for OLPBlackbox_Target objects.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */
class OLPBlackbox_TargetStateData extends OLPBlackbox_StateData
{
	/**
	 * Constructs an OLPBlackbox_TargetStateData object, mostly concerned with setting allowed_targets.
	 * 
	 * Note: Data set in constructor ignores mutable/immutable status.
	 *
	 * @param array $data assoc array of data to initialize the state object with.
	 *
	 * @return void
	 */
	function __construct($data = NULL)
	{
		// initialize allowed_keys for things that make sense for OLPBlackbox_Targets
		$this->immutable_keys[] = 'target_name';
		$this->immutable_keys[] = 'target_id';
		$this->immutable_keys[] = 'target_tags';

		$this->mutable_keys[] = 'is_react';
		$this->mutable_keys[] = 'react_app_id';
		$this->mutable_keys[] = 'qualified_loan_amount';
		$this->mutable_keys[] = 'suppression_lists';
		$this->mutable_keys[] = 'list_mgmt_nosell';
		$this->mutable_keys[] = 'look_percentages';

		/* {@see OLPBlackbox_Rule_LenderPost} */
		$this->mutable_keys[] = 'lender_post_result';
		
		$this->mutable_keys[] = 'eventlog_show_rule_passes';
		
		parent::__construct($data);
	}
}
?>
