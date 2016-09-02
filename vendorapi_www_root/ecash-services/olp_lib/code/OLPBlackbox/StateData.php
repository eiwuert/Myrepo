<?php
/**
 * Class definition for OLPBlackbox_StateData.
 *
 * @author Rob Voss <rob.voss@sellingsource.com>
 */

/**
 * Class for holding state information for OLPBlackbox_DataX classes.
 *
 * @author Rob Voss <rob.voss@sellingsource.com>
 */
class OLPBlackbox_StateData extends Blackbox_StateData
{
	/**
	 * Constructs a OLPBlackbox_StateData object, mostly concerned with setting allowed_keys.
	 *
	 * Note: Data set in constructor ignores mutable/immutable status.
	 *
	 * @param array $data assoc array of data to initialize the state object with.
	 *
	 * @return void
	 */
	function __construct($data = NULL)
	{
		/* initialize allowed_keys for things that make sense for OLPBlackbox_DataX
		 * using immutable_keys or mutable_keys depending if rules should be able to change them.
		 */
		$this->mutable_keys[] = 'uw_decision';
		$this->mutable_keys[] = 'track_hash';
		$this->mutable_keys[] = 'loan_amount_decision';

		$this->mutable_keys[] = 'snapshot';

		// Stores global rule app failed on
		$this->mutable_keys[] = 'global_rule_failure';

		// added for GForge 11603 to provide a place for ecash_react
		// failure reasons to be recorded.
		$this->mutable_keys[] = 'failure_reasons';

		// delegates to be invoked after blackbox runs
		$this->immutable_keys[] = 'deferred';
		
		// stores repsonse data from lenders across posts to different campaigns
		$this->mutable_keys[] = 'lender_post_persistent_data';
		
		// stores the response from the datax aba validation call
		$this->mutable_keys[] = 'datax_aba_decision';

		// TODO: initialize snapshot here

		parent::__construct($data);
	}
	
	/**
	 * Get the current runtime state in an array
	 *
	 * @return array
	 */
	public function __sleep()
	{
		return array('data', 'data_objects', 'mutable_keys', 'immutable_keys');
	}
}
?>
