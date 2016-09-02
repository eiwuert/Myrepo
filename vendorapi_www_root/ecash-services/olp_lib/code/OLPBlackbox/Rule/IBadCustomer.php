<?php
/**
 * Interface for the BadCustomer rules.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
interface OLPBlackbox_Rule_IBadCustomer
{
	/**
	 * Returns the cache key.
	 * 
	 * If $key is a string, it will return the cache key prefix attached with $key. If $key is an array, it will
	 * generate the suffix by using the getKey() method.
	 *
	 * @param mixed $key
	 * @return string
	 */
	public function getCacheKey($key);
}
