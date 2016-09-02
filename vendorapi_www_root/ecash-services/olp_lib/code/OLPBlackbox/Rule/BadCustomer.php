<?php
/**
 * Bad customers.
 * 
 * @see [#16642] Black Box - ZIPCASH - Price Rejection Process [DY]
 * @author Demin.Yin <Demin.Yin@SellingSource.com>
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class OLPBlackbox_Rule_BadCustomer extends OLPBlackbox_Rule implements OLPBlackbox_Rule_IBadCustomer
{
	/**
	 * The number of seconds the cache should be valid.
	 */
	const CACHE_DURATION = 120;
	
	/**
	 * Runs a check against table bad_customer.
	 *
	 * @param Blackbox_Data $data the data we run the rule on
	 * @param Blackbox_IStateData $state_data state data passed to us
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$data_array = $this->getDataValue($data);
		$key = $this->getKey($data_array);

		$exist = $this->getCacheValue($this->getCacheKey($key));
		
		if (strcasecmp($exist, 'Y') == 0)
		{
			return FALSE;
		}
		elseif (strcasecmp($exist, 'N') == 0)
		{
			return TRUE;
		}
		else
		{
			return $this->isBadCustomer($key);
		}
	}
	
	/**
	 * Determines if the customer identified by $key is in the bad_customer table.
	 *
	 * @param string $key
	 * @return bool
	 */
	protected function isBadCustomer($key)
	{
		$db = $this->getOLPConnection();
		$query = "
			SELECT
				IF(COUNT(*) > 0, 'Y', 'N') AS exist
			FROM
				bad_customer
			WHERE
				vendor = :vendor_number
				AND data = :data
				AND (expiration_date >= NOW() OR expiration_date IS NULL)
			LIMIT 1";
		$result = DB_Util_1::queryPrepared(
			$db,
			$query,
			array('vendor_number' => $this->getVendorNumber(), 'data' => $key)
		);
		$exists = $result->fetchColumn();
		$this->addCacheValue($this->getCacheKey($key), $exists, self::CACHE_DURATION);
		
		return ($exists != 'Y');
	}

	/**
	 * Returns the key.
	 *
	 * @param array $data
	 * @return string
	 */	
	abstract protected function getKey(array $data);
	
	/**
	 * Returns the cache prefix to use for the key.
	 *
	 * @return string
	 */
	abstract protected function getCachePrefix();
	
	/**
	 * Returns the vendor number.
	 *
	 * @return int
	 */
	abstract protected function getVendorNumber();
}
