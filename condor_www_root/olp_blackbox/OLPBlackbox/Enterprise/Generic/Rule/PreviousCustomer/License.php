<?php

/**
 * The previous customer check by driver's license
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomer_License extends OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomer
{
	/**
	 * Gives a short name for the rule
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'DRIVERS_LICENSE';
	}

	/**
	 * Indicates whether the rule has the proper information to run
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		return isset($data->state_id_number);
	}

	/**
	 * Gets the conditions for the ECash provider
	 *
	 * @param Blackbox_Data $data
	 * @return array
	 */
	protected function getECashConditions(Blackbox_Data $data)
	{
		return array(
			'legal_id_number' => $data->state_id_number,
		);
	}

	/**
	 * Gets the conditions for the OLP provider
	 *
	 * @param Blackbox_Data $data
	 * @return unknown
	 */
	protected function getOLPConditions(Blackbox_Data $data)
	{
		return NULL;
	}
}

?>