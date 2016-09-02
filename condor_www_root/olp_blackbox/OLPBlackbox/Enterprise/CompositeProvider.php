<?php

/**
 * A composite history provider
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_CompositeProvider implements OLPBlackbox_Enterprise_ICustomerHistoryProvider
{
	/**
	 * @var array
	 */
	protected $provider = array();

	/**
	 * Adds a provider to the composite
	 *
	 * @param OLPBlackbox_Enterprise_ICustomerHistoryProvider $p
	 * @return void
	 */
	public function addProvider(OLPBlackbox_Enterprise_ICustomerHistoryProvider $p)
	{
		$this->provider[] = $p;
	}

	/**
	 * Finds customer history by SSN
	 *
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @param string $ssn
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	public function fromSSN(OLPBlackbox_Enterprise_CustomerHistory $history, $ssn)
	{
		foreach ($this->provider as $p)
		{
			$history = $p->fromSSN($history, $ssn);
		}
		return $history;
	}

	/**
	 * Finds customer history by email address
	 *
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @param string $email
	 * @param string $dob
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	public function fromEmailDob(OLPBlackbox_Enterprise_CustomerHistory $history, $email, $dob)
	{
		foreach ($this->provider as $p)
		{
			$history = $p->fromEmailDob($history, $email, $dob);
		}
		return $history;
	}

	/**
	 * Finds customer history by bank account and SSN
	 *
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @param string $aba
	 * @param string $account
	 * @param string $ssn
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	public function fromBankAccount(OLPBlackbox_Enterprise_CustomerHistory $history, $aba, $account, $ssn)
	{
		foreach ($this->provider as $p)
		{
			$history = $p->fromBankACcount($history, $aba, $account, $ssn);
		}
		return $history;
	}

	/**
	 * Finds customer history by home phone and DOB
	 *
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @param string $home_phone
	 * @param int $dob timestamp
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	public function fromPhoneDob(OLPBlackbox_Enterprise_CustomerHistory $history, $home_phone, $dob)
	{
		foreach ($this->provider as $p)
		{
			$history = $p->fromPhoneDob($history, $home_phone, $dob);
		}
		return $history;
	}

	/**
	 * Finds customer history by license number
	 *
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @param string $license_num
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	public function fromLicense(OLPBlackbox_Enterprise_CustomerHistory $history, $license_num)
	{
		foreach ($this->provider as $p)
		{
			$history = $p->fromLicense($history, $license_num);
		}
		return $history;
	}
}

?>