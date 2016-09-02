<?php
/**
 * Implements an interface to the memcache API.
 * 
 * See http://us.php.net/manual/en/function.Memcache-addServer.php for more information
 * on some of the variables.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 * @copyright 2007 Selling Source, Inc.
 */
class Memcache_Singleton
{
	/**
	 * Instance of the Memcache_Singleton class.
	 * 
	 * This is initialized throught the Get_Instance() function.
	 *
	 * @var Memcache_Singleton
	 */
	private static $instance;
	
	/**
	 * Array of process servers that are running memcached.
	 * 
	 * Available Options:
	 * 
	 * host - The host name of the server.
	 * port - The port the server is running on. If not set, will assume default port.
	 * online - If the server should be considered active. If not set, will assume active.
	 * buckets - The number of buckets that server contains. If not set, will assume default of 1.
	 *
	 * @var array
	 */
	private $process_servers = array(
		array('host' => 'ps1.ept.tss'),
		array('host' => 'ps2.ept.tss', 'online' => FALSE), // Server is being moved to eCash
		array('host' => 'ps3.ept.tss', 'online' => FALSE), // Server is being moved to eCash
		array('host' => 'ps4.ept.tss', 'online' => FALSE), // Server is being moved to eCash
		array('host' => 'ps10.ept.tss'),
		array('host' => 'ps11.ept.tss', 'buckets' => 2),
		array('host' => 'ps12.ept.tss', 'buckets' => 2),
		array('host' => 'ps30.ept.tss', 'buckets' => 2));
	
	/**
	 * Instance of the memcache object.
	 *
	 * @var object
	 */
	private $memcache;
	
	/**
	 * Variable determining of the memcache module exists.
	 *
	 * @var unknown_type
	 */
	private $module_exists = TRUE;
	
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
	 * Memcache_Singleton constructor. Private so that no one can directly
	 * instantiate this object without using Get_Instance().
	 */
	private function __construct()
	{
		// Does nothing
	}
	
	/**
	 * Overrides the clone object. Private so that no one can clone this object.
	 *
	 */
	private function __clone() {}
	
	/**
	 * Returns an instance of the Memcache_Singleton class.
	 *
	 * @return object
	 */
	public static function Get_Instance()
	{
		return Cache_Memcache::getInstance();
	}
	
	/**
	 * Sets the value var with the key in memcache.
	 *
	 * @param string $key
	 * @param mixed $var
	 * @param int $expire
	 * @param int $flag
	 * @return bool
	 */
	public function set($key, $var, $expire = self::MEMCACHE_EXPIRE, $flag = 0)
	{
		return Cache_Memcache::getInstance()->set($key, $var, $expire, $flag);
	}
	
	/**
	 * Returns the value in memcache associated with the key or false if the key doesn't exist.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get($key)
	{
		return Cache_Memcache::getInstance()->get($key);
	}
	
	/**
	 * Deletes a value in memcache based on the key.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function delete($key)
	{
		return Cache_Memcache::getInstance()->delete($key);
	}
	
	/**
	 * Adds a value to memcache with the specified key. Will only add the value if the key doesn't
	 * already exist.
	 *
	 * @param string $key
	 * @param mixed $var
	 * @param int $expire
	 * @param int $flag
	 * @return bool
	 */
	public function add($key, $var, $expire = self::MEMCACHE_EXPIRE, $flag = 0)
	{
		return Cache_Memcache::getInstance()->add($key, $var, $expire, $flag);
	}
}
?>
