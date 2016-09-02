<?php

/**
 * Handles accept ratio monitoring, DB interaction for the the ill-named
 * "BB Frequency Scoring" GF[#3833] and GF[#5565].
 *
 * @author Tym Feindel <timothy.feindel@sellingsource.com>
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_FrequencyScore
{
	const MEMCACHE_TIMEOUT = 600;
	
	/**
	 * @var DB_IConnection_1
	 */
	protected $db;
	
	/**
	 * @var Cache_Memcache
	 */
	protected $memcache;
	
	/**
	 * @param DB_IConnection_1 $db
	 * @param Cache_Memcache $memcache
	 */
	public function __construct(DB_IConnection_1 $db, Cache_Memcache $memcache)
	{
		$this->db = $db;
		$this->memcache = $memcache;
	}
	
	/**
	 * Return the total number of rejects in the past $past_period timeframe
	 * for this email address.
	 *
	 * @param string $email
	 * @param string $past_period
	 * @return int
	 */
	public function getRejectsByHistory($email, $past_period)
	{
		$past_period = date('Y-m-d H:i:s', strtotime("-{$past_period}"));
		
		$query = "
			SELECT
				SUM(declined_sum) AS declines
			FROM
				vendor_decline_freq
			WHERE
				date_created > ?
				AND client_email = ?
		";
		
		$declines = DB_Util_1::querySingleValue($this->db, $query, array($past_period, $email));
		
		// Add in score from memcache (soap apps)
		$declines += $this->getMemScore($email);
		
		return $declines;
	}
	
	/**
	 * Increment the memcache value of email address.
	 *
	 * @param string $email
	 * @return void
	 */
	public function addPost($email)
	{
		$this->memcache->set(
			$this->getMemcacheKey($email),
			$this->getMemScore($email) + 1,
			self::MEMCACHE_TIMEOUT
		);
	}
	
	/**
	 * This email address was accepted, store to database.
	 *
	 * @param string $email
	 * @param string $property_short
	 * @param int $application_id
	 */
	public function addAccept($email, $property_short, $application_id)
	{
		$query = "
			INSERT INTO
				vendor_decline_freq (date_created, client_email, declined_sum, accept_property_short, application_id)
			VALUES
				(NOW(), ?, ?, ?, ?)
		";
		
		$params = array(
			$email,
			$this->getMemScore($email),
			$property_short,
			$application_id,
		);
		
		DB_Util_1::queryPrepared($this->db, $query, $params);
		
		// Once inserted into database, blank out the memcache value
		$this->removeMemScore($email);
	}
	
	/**
	 * Retrieves the current frequency score by email.
	 *
	 * @param string $email
	 * @return int
	 */
	public function getMemScore($email)
	{
		// If key doesn't exist, default to 0.
		$value = $this->memcache->get($this->getMemcacheKey($email));
		if ($value === FALSE) $value = 0;
		
		return $value;
	}
	
	/**
	 * Zeroes out the frequency score in memcache.
	 *
	 * @param string $email
	 * @return void
	 */
	public function removeMemScore($email)
	{
		
		$this->memcache->delete($this->getMemcacheKey($email));
	}
	
	/**
	 * Generates the key for an email address.
	 *
	 * @param string $email
	 * @return string
	 */
	protected function getMemcacheKey($email)
	{
		return 'AR' . strtoupper($email);
	}
	
	/**
	 * Return the sum of declines in 1 hour, 1 day, and 1 week intervals for an email address.
	 *
	 * @param string $email
	 * @return array
	 */
	public function getPeriodicDeclines($email)
	{
		$query = "
			SELECT
				SUM(IF(date_created > DATE_SUB(NOW(), INTERVAL 1 HOUR), declined_sum, 0)) AS hour,
				SUM(IF(date_created > DATE_SUB(NOW(), INTERVAL 1 DAY), declined_sum, 0)) AS day,
				SUM(IF(date_created > DATE_SUB(NOW(), INTERVAL 1 WEEK), declined_sum, 0)) AS week
			FROM
				vendor_decline_freq
			WHERE
				date_created > DATE_SUB(NOW(), INTERVAL 1 WEEK)
				AND client_email = ?
			";
		
		$declines = DB_Util_1::querySingleRow($this->db, $query, array($email));
		
		return $declines;
	}
}

?>
