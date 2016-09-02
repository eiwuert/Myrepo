<?php

/**
 * Implements a fake Memcache.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Cache_FakeMemcache extends Cache_Memcache
{
	/**
	 * The array that contains all memcached data.
	 *
	 * @var array
	 */
	protected $data;
	
	/**
	 * Initializes (and publicizes) the constructor.
	 */
	public function __construct()
	{
		$this->data = array();
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
		$this->data[$key] = $var;
		return TRUE;
	}
	
	/**
	 * Returns the value in memcache associated with the key or false if the key doesn't exist.
	 *
	 * @param string $key the key for data in memcache
	 * @return mixed
	 */
	public function get($key)
	{
		if (isset($this->data[$key])) return $this->data[$key];
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
		if (isset($this->data[$key]))
		{
			unset($this->data[$key]);
			return TRUE;
		}
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
		if (!isset($this->data[$key]))
		{
			$this->data[$key] = $var;
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Flushes the entire cache.
	 *
	 * @return bool
	 */
	public function flush()
	{
		$this->data = array();
		return TRUE;
	}
	
	/**
	 * Adds a server to the memcache server pool.
	 *
	 * @param Cache_MemcacheServer $server server to add to the pool
	 * @return bool
	 */
	public function addServer(Cache_MemcacheServer $server)
	{
		return TRUE;
	}
}
?>
