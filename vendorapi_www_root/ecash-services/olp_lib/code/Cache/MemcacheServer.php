<?php
/**
 * Data transfer object for Memcache server configurations.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Cache_MemcacheServer
{
	/**
	 * The host name of the server.
	 *
	 * @var string
	 */
	protected $host;
	
	/**
	 * The port number of the server.
	 *
	 * @var int
	 */
	protected $port;
	
	/**
	 * Whether the server connection is persistent.
	 *
	 * @var bool
	 */
	protected $persistent;
	
	/**
	 * The number of buckets for this server.
	 *
	 * @var int
	 */
	protected $weight;
	
	/**
	 * The time in seconds before this connection will timeout.
	 *
	 * @var int
	 */
	protected $timeout;
	
	/**
	 * How often a failed server will retry.
	 *
	 * @var int
	 */
	protected $retry_interval;
	
	/**
	 * Determines if the server is online.
	 *
	 * @var bool
	 */
	protected $status;
	
	/**
	 * MemcacheServer constructor.
	 *
	 * @param string $host the memcache host name
	 * @param int $port the memcache port number
	 * @param bool $persistent whether the server connection is persistent
	 * @param int $weight the weight of the server
	 * @param int $timeout the timeout on a connection
	 * @param int $retry_interval how many times it will wait to retry the server
	 * @param bool $status whether the server is active
	 */
	public function __construct(
		$host,
		$port           = 11211,
		$persistent     = TRUE,
		$weight         = 1,
		$timeout        = 1,
		$retry_interval = 15,
		$status         = TRUE)
	{
		if (!is_string($host) || strlen($host) < 2)
		{
			throw new InvalidArgumentException('Host must be a string of at least 2 characters.');
		}
		
		if (!is_int($port) || $port <= 0)
		{
			throw new InvalidArgumentException('Port must be a positive integer.');
		}
		
		if (!is_bool($persistent))
		{
			throw new InvalidArgumentException('Persistence must be a boolean.');
		}
		
		if (!is_int($weight) || $weight <= 0)
		{
			throw new InvalidArgumentException('Weight must be a positive integer.');
		}
		
		if (!is_int($timeout) || $timeout <= 0)
		{
			throw new InvalidArgumentException('Timeout must be a positive integer.');
		}
		
		if (!is_int($retry_interval) || $retry_interval <= 0)
		{
			throw new InvalidArgumentException('Retry interval must be a positive integer.');
		}
		
		if (!is_bool($status))
		{
			throw new InvalidArgumentException('Status must be a boolean.');
		}
		
		$this->host           = $host;
		$this->port           = $port;
		$this->persistent     = $persistent;
		$this->weight         = $weight;
		$this->timeout        = $timeout;
		$this->retry_interval = $retry_interval;
		$this->status         = $status;
	}
	
	/**
	 * Sets the server to be inactive, while remaining in the loop.
	 *
	 * @return void
	 */
	public function setInactive()
	{
		$this->status = FALSE;
		$this->retry_interval = -1;
	}
	
	/**
	 * Overloaded __get() method for retrieving class properties.
	 *
	 * @param string $name the name of the property to get
	 * @return mixed
	 */
	public function __get($name)
	{
		if (property_exists($this, $name))
		{
			return $this->$name;
		}
		
		throw new InvalidArgumentException("$name does not exist as a property.");
	}
}
?>