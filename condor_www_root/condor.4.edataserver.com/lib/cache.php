<?php
/**
 * Caching Implementation for Condor
 *
 */

class Cache
{
	protected $module_exists;
	protected $memcache;
	protected $mode;
	
	
	protected static $servers;
	protected static $instance;
	
	const MEMCACHE_EXPIRE = 1800;
	const MEMCACHE_PERSISTANT = true;
	const MEMCACHE_WEIGHT = 1;
	const MEMCACHE_PORT = 11211;
	const MEMCACHE_SET_FLAGS = 0;
	
	/**
	 * Creates a cache object for a particular
	 * mode.
	 *
	 * @param string $mode
	 */
	public function __construct($mode = 'RC')
	{
		//Make sure we both have memcache class AND that we have defined
		//servers for this mode.
		if(extension_loaded('memcache') && is_array(self::$servers[$mode]))
		{
			$this->module_exists = true;
			$this->memcache = new Memcache;
			$this->AddServersToMemcache($mode);
			$this->mode = $mode;
		}
		else 
		{
			$this->memcache = FALSE;
		}
	}
	
	/**
	 * Defines server information for a server
	 * to be used in a mode.
	 *
	 * @param string $mode
	 * @param string $host
	 * @param int $port
	 * @param boolean $persistant
	 * @param int $weight
	 */
	public static function DefineServer($mode,
			$host,
			$port = self::MEMCACHE_PORT,
			$persistant = self::MEMCACHE_PERSISTANT,
			$weight = self::MEMCACHE_PORT)
	{
		$o = new stdClass();
		$o->host = $host;
		$o->port = $port;
		$o->persistant = $persistant;
		$o->weight = $weight;
		if(!is_array(self::$servers)) self::$servers = array();
		if(!is_array(self::$servers[$mode])) self::$servers[$mode] = array(); 
		self::$servers[$mode][] = $o;
	}
	
	/**
	 * Loops through all the defined servers for a particular mode
	 * and adds them to the memcache object
	 *
	 * @param string $mode
	 */
	protected function AddServersToMemcache($mode)
	{
		if($this->module_exists && is_array(self::$servers[$mode]))
		{
			foreach(self::$servers[$mode] as $server)
			{
				$this->memcache->addServer($server->host, 
					$server->port, 
					$server->persistant, 
					$server->weight);
			}
		}
	}
	
	/**
	 * Overload __get to use memcache Get
	 *
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->Get($key);
	}
	
	/**
	 * Overload the __set to use memcache Set 
	 *
	 * @param string $key
	 * @param mixed $var
	 */
	public function __set($key, $var)
	{
		return $this->Set($key, $var);
	}
	
	/**
	 * Returns the value associated with $key in
	 * memcache
	 *
	 * @param mixed $key
	 * @return mixed
	 */
	public function Get($key)
	{
		return $this->module_exists ? $this->memcache->get($key) : FALSE;
	}
	
	public function Delete($key)
	{
		return $this->module_exists ? $this->memcache->delete($key) : FALSE;
	}
	
	/**
	 * Sets a variable in memcache if we're using the module
	 *
	 * @param string $key
	 * @param mixed $var
	 * @param int $expire
	 * @param int $flag
	 */
	public function Set($key, 
			$var, 
			$expire = self::MEMCACHE_EXPIRE, 
			$flag = self::MEMCACHE_SET_FLAGS)
	{
		if($this->module_exists === true)
		{
			if(!is_numeric($expire)) $expire = self::MEMCACHE_EXPIRE;
			return $this->memcache->Set($key, $var, $flag, $expire);
		}
		else 
		{
			return false;
		}
	}
	
	public function increment($key, $value = NULL)
	{
		if(!is_null($value))
		{
			return $this->memcache->increment($key,$value);
		}
		else 
		{
			$this->memcache->increment($key);
		}
		
	}
	
	/**
	 * Returns the extended stats stuff for this memcache object
	 *
	 * @param unknown_type $type
	 * @param unknown_type $slabid
	 * @param unknown_type $limit
	 * @return unknown
	 */
	public function getExtendedStats($type = NULL, $slabid = NULL, $limit = NULL)
	{
		return $this->memcache->getExtendedStats($type, $slabid, $limit);
	}
	
	/**
	 * Returns a singleton instance of the Cache class.
	 *
	 * @param string $mode
	 * @return Cache
	 */
	public function Singleton($mode = 'RC')
	{
		if(!isset(self::$instance) || !self::$instance instanceof Cache)
		{
			self::$instance = new Cache($mode);
		}
		return self::$instance;
	}
	
	
}