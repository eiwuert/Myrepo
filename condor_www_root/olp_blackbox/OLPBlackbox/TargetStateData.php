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
class OLPBlackbox_TargetStateData extends Blackbox_StateData
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
		$this->mutable_keys[] = 'withheld_targets';
		$this->mutable_keys[] = 'is_react';
		$this->mutable_keys[] = 'qualified_loan_amount';
		$this->mutable_keys[] = 'suppression_lists';
		$this->mutable_keys[] = 'list_mgmt_nosell';
		$this->mutable_keys[] = 'look_percentages';
		$this->mutable_keys[] = 'vetting_react_sold';

		parent::__construct($data);
	}
}
?>
