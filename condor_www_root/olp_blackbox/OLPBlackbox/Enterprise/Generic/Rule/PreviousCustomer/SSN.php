<?php

/**
 * The previous customer check by SSN
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomer_SSN extends OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomer
{
	/**
	 * Gives a short name for the rule
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'SSN';
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
		return (isset($data->social_security_number)
			&& strlen($data->social_security_number) == 9);
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
			'ssn' => $data->social_security_number,
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
		return array(
			'social_security_number' => $data->social_security_number_encrypted,
		);
	}
}

?>