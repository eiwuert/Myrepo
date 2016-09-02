<?php

/**
 * A memcache store purchased leads.
 *
 * This class is primarily used to assist in preventing leads from a single
 * customer from being sold to the same company within a short period of time.
 * Due to the asynchronous nature of lead processing it used to be feasible for
 * leads to sneak in while other leads were waiting to be scrubbed.
 *
 * Applications are stored via SSN. A locking mechanism is available to
 * prevent race conditions from causing issues. This class will handle failures
 * gracefully and optimisticly. If there is an error connecting to the data
 * store then it will be assumed that locks could be acheived and that
 * applications have not been cached.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_PurchasedLeadStore_Memcache
{
	const KEY_PREFIX = 'PLS';
	const LEADLIST_PREFIX = 'LeadList';
	const SSNLOCK_PREFIX = 'SSNLock';

	/**
	 * Temporary fix for #54795 - Reduce the cache time to 1 hour
	 * from the original value of 30 Days (2952000 Seconds)
	 */
	const LEAD_CACHE_TIME = 3600;

	/**
	 * @var Memcache
	 */
	protected $cache;
	
	/**
	 * @var String
	 */
	protected $cache_prefix;

	/**
	 * @param string $ssn
	 * @param string $company
	 * @param Memcache $cache
	 */
	public function __construct($prefix, Memcache $cache)
	{
		$this->cache = $cache;
		$this->cache_prefix = $prefix;
	}

	/**
	 * Creates a lock on the ssn.
	 *
	 * @param string ssn
	 * @param int $usec_to_block
	 * @return BOOL
	 */
	public function lockSsn($ssn, $usec_to_block = NULL)
	{
		$try_till = microtime(TRUE) + $usec_to_block * 0.000001;
		$success = $this->cache->add($this->getSsnLockKey($ssn), 1, 0, 300);
		while (microtime(TRUE) < $try_till && !$success)
		{
			usleep(50);
			$success = $this->cache->add($this->getSsnLockKey($ssn), 1, 0, 300);
		}
		return $success;
	}

	/**
	 * Removes a lock on a particular ssn.
	 *
	 * @param string ssn
	 * @return NULL
	 */
	public function unlockSsn($ssn)
	{
		$this->cache->delete($this->getSsnLockKey($ssn), 0);
	}

	/**
	 * Adds another application to the application store.
	 *
	 * @param string $ssn
	 * @param string $company
	 * @param int $application_id
	 * @param int $date - Unix Time Stamp
	 * @return NULL
	 */
	public function addApplication($ssn, $company, $application_id, $date)
	{
		$leads = $this->cache->get($this->getLeadListKey($ssn));
		
		if (!is_array($leads))
		{
			$leads = array();
		}
		
		$leads[$application_id] = array(
			'application_id' => $application_id,
			'ssn' => $ssn,
			'company' => $company,
			'date' => $date
		);

		$this->cache->set($this->getLeadListKey($ssn), $leads, 0, self::LEAD_CACHE_TIME);
	}

	/**
	 * Removes an application from the application store.
	 *
	 * @param string $ssn
	 * @param string $application_id
	 * @return NULL
	 */
	public function removeApplication($ssn, $application_id)
	{
		$leads = $this->cache->get($this->getLeadListKey($ssn));

		if (is_array($leads) && isset($leads[$application_id]))
		{
			unset($leads[$application_id]);

			$this->cache->set($this->getLeadListKey($ssn), $leads, 0, self::LEAD_CACHE_TIME);
		}
	}

	/**
	 * Returns the applications for the given ssn in the lead store.
	 *
	 * @param string $ssn
	 * @return array
	 */
	public function getApplications($ssn)
	{
		$leads = $this->cache->get($this->getLeadListKey($ssn));

		return is_array($leads) ? $leads : array();
	}

	/**
	 * Returns a properly formatted key for storing lead lists.
	 *
	 * @param string $ssn
	 * @return string
	 */
	protected function getLeadListKey($ssn)
	{
		return self::KEY_PREFIX . '-' . self::LEADLIST_PREFIX . '-' . $this->cache_prefix . '-' . $ssn;
	}

	/**
	 * Returns a properly formatted key for storing lead lists.
	 *
	 * @param string $ssn
	 * @return string
	 */
	protected function getSsnLockKey($ssn)
	{
		return self::KEY_PREFIX . '-' . self::SSNLOCK_PREFIX . '-' . $this->cache_prefix. '-' . $ssn;
	}
}

?>
