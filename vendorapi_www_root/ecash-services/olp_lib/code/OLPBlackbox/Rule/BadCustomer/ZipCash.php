<?php
/**
 * The ZipCash Bloom filter rule.
 * 
 * @see class OLPBlackbox_Rule_CashNet_BloomFilter
 * @see [#16642] Black Box - ZIPCASH - Price Rejection Process [DY]
 * @author Demin.Yin <Demin.Yin@SellingSource.com>
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_BadCustomer_ZipCash extends OLPBlackbox_Rule_BadCustomer
{
	/**
	 * Returns the cache key.
	 *
	 * @param array $data
	 * @return string
	 */
	public function getKey(array $data)
	{
		$email = $data['email_primary'];
		$ssn = $data['social_security_number'];

		$key = substr($email, 0, strpos($email, '@')). '|' . substr($ssn, -4);
		$key = strtolower($key);
		
		return $key;
	}
	
	/**
	 * Returns the cache prefix.
	 *
	 * @return string
	 */
	protected function getCachePrefix()
	{
		return 'RULE:BC:ZC:';
	}
	
	/**
	 * Returns the cache key.
	 *
	 * @param mixed key
	 * @return string
	 */
	public function getCacheKey($key)
	{
		if (is_string($key)) return $this->getCachePrefix() . $key;
		elseif (is_array($key)) return $this->getCachePrefix() . $this->getKey($key);
		
		throw new RuntimeException('key must be a string or an array');
	}
	
	/**
	 * Returns the vendor number.
	 *
	 * @return int
	 */
	protected function getVendorNumber()
	{
		return CompanyData::getVendorNumber(CompanyData::COMPANY_ZIPCASH);
	}
}
