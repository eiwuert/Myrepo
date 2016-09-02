<?php

/**
 * The previous customer check factory for Agean
 * They use the basic checks, except their active threshold is 0 and
 * they don't include recovered apps as paid
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Agean_Factory_Legacy_PreviousCustomer extends OLPBlackbox_Enterprise_Generic_Factory_Legacy_PreviousCustomer
{
	const ACTIVE_THRESHOLD = 0;

	/**
	 * Gets the company's decider
	 * Have to redefine this to get the constant to work
	 *
	 * @return OLPBlackbox_Enterprise_ICustomerHistoryDecider
	 */
	protected function getDecider()
	{
		return new OLPBlackbox_Enterprise_Generic_Decider(
			self::ACTIVE_THRESHOLD,
			self::DENIED_THRESHOLD
		);
	}

	/**
	 * Gets the ECash provider
	 *
	 * @param string $target_name
	 * @return OLPBlackbox_Enterprise_ECashProvider
	 */
	protected function getECashProvider(array $companies)
	{
		$provider = new OLPBlackbox_Enterprise_Agean_ECashProvider(
			$companies,
			$this->expire,
			$this->preact
		);
		return $provider;
	}
}

?>