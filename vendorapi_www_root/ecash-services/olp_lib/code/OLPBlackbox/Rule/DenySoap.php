<?php
/**
 * OLPBlackbox_Rule_DenySoap class file.
 *
 * @author Tym Feindel <timpthy.feindel@sellingsource.com>
 */

/**
 * Checks to make sure the current site is non-soap, rejects if soapy
 * @author Tym Feindel <timpthy.feindel@sellingsource.com>
 */
class OLPBlackbox_Rule_DenySoap extends OLPBlackbox_Rule implements OLPBlackbox_Factory_Legacy_IReusableRule
{
	/**
	 * We dont need to do the normal data value check, always return true.
	 *
	 * @param Blackbox_Data $data the data the rule is running against
	 * @param Blackbox_IStateData $state_data information about the state of the Blackbox_ITarget which desires to run the rule.
	 *
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}
	
	/**
	 * Runs the DenySoap rule, will fail if it's a SOAP app
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 * 
	 * @return bool
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// If it's not a soap app, return true
		return (empty($data->is_soap));
	}
}

?>
