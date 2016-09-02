<?php
/**
 * Implements an interface to the memcache API.
 * 
 * See http://us.php.net/manual/en/function.Memcache-addServer.php for more information
 * on some of the variables.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Cache_Memcache
{
	/**
	 * Instance of the Memcache_Singleton class.
	 * 
	 * This is initialized throught the Get_Instance() function.
	 *
	 * @var Cache_Memcache
	 */
	private static $instance;
	
	/**
	 * Instance of the memcache object.
	 *
	 * @var Memcache
	 */
	protected $memcache;
	
	/**
	 * Variable determining of the memcache module exists.
	 *
	 * @var bool
	 */
	protected $module_exists = TRUE;
	
	/**
	 * Default memcache port.
	 */
	const MEMCACHE_PORT = 11211;
	
	/**
	 * Default memcache entry expire time.
	 * 
	 * This is the default time that an item will be stored in memcache.
	 * Defaults to a 15 minute expire time.
	 */
	const MEMCACHE_EXPIRE = 900;
	
	/**
	 * Default memcache persistant connection setting.
	 */
	const MEMCACHE_PERSISTANT = TRUE;
	
	/**
	 * The default number of memcache buckets.
	 * 
	 * This is essentially the weight of the server.
	 */
	const MEMCACHE_BUCKETS = 1;
	
	/**
	 * The default memcache timeout setting in seconds.
	 */
	const MEMCACHE_TIMEOUT = 1;
	
	/**
	 * The default memcache retry interval in seconds.
	 */
	const MEMCACHE_RETRY_INTERVAL = 15;
	
	/**
	 * The default memcache retry interval when the server is considered offline.
	 */
	const MEMCACHE_RETRY_INTERVAL_OFFLINE = -1;
	
	/**
	 * The default memcache server status.
	 */
	const MEMCACHE_STATUS = TRUE;
	
	/**
	 * Memcache_Singleton constructor. Protected so that no one can directly
	 * instantiate this object without using getInstance().
	 */
	protected function __construct()
	{
		if (extension_loaded('memcache'))
		{
			$this->memcache = new Memcache;
		}
		else
		{
			$this->module_exists = FALSE;
		}
	}
	
	/**
	 * Overrides the clone object. Private so that no one can clone this object.
	 * 
	 * @return void
	 */
	private function __clone()
	{
		// Does nothing
	}
	
	/**
	 * Returns an instance of the Memcache_Singleton class.
	 *
	 * @return Cache_Memcache
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			self::$instance = new Cache_Memcache();
		}
		
		return self::$instance;
	}
	
	/**
	 * Sets the value var with the key in memcache.
	 *
	 * @param string $key    the key for this data in cache
	 * @param mixed  $var    the data to save in cache
	 * @param int    $expire the amount of time in seconds for this entry to expire
	 * @param int    $flag   flags to memcache
	 * @return bool
	 */
	public function set($key, $var, $expire = self::MEMCACHE_EXPIRE, $flag = 0)
	{
		if ($this->module_exists) return $this->memcache->set($key, $var, $flag, $expire);
		return FALSE;
	}
	
	/**
	 * Returns the value in memcache associated with the key or false if the key doesn't exist.
	 *
	 * @param string $key the key for data in memcache
	 * @return mixed
	 */
	public function get($key)
	{
		if ($this->module_exists) return $this->memcache->get($key);
		return FALSE;
	}
	
	/**
	 * Deletes a value in memcache based on the key.
	 *
	 * @param string $key the key for data in memcache
	 * @return bool
	 */
	public function delete($key)
	{
		if ($this->module_exists) return $this->memcache->delete($key);
		return FALSE;
	}
	
	/**
	 * Adds a value to memcache with the specified key. Will only add the value if the key doesn't
	 * already exist.
	 *
	 * @param string $key    the key for data in memcache
	 * @param mixed  $var    the data to save in cache
	 * @param int    $expire the amount of time in seconds for this entry to expire
	 * @param int    $flag   flags to memcache
	 * @return bool
	 */
	public function add($key, $var, $expire = self::MEMCACHE_EXPIRE, $flag = 0)
	{
		if ($this->module_exists) return $this->memcache->add($key, $var, $flag, $expire);
		return FALSE;
	}
	
	/**
	 * Adds a server to the memcache server pool.
	 *
	 * @param Cache_MemcacheServer $server server to add to the pool
	 * @return bool
	 */
	public function addServer(Cache_MemcacheServer $server)
	{
		if ($this->module_exists)
		{
			return $this->memcache->addServer(
				$server->host,
				$server->port,
				$server->persistent,
				$server->weight,
				$server->timeout,
				$server->retry_interval,
				$server->status
			);
		}
		
		return FALSE;
	}
}
?>
