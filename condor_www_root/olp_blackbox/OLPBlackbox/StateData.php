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
		$this->mutable_keys[] = 'datax_decision';
		$this->mutable_keys[] = 'track_hash';

		$this->mutable_keys[] = 'snapshot';
		
		// "legacy_state_reject" maps to the old blackbox session variable
		// WVVAGA_CHECK which was set to be used by OLP
		$this->mutable_keys[] = 'legacy_state_fail';
		$this->legacy_state_fail = FALSE; 
		
		// TODO: initialize snapshot here

		parent::__construct($data);
	}
}
?>
