<?php

	/**
	 * @package Cache
	 */

	/**
	 * A cache store that uses Memcached
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Cache_MemcachedStore_1 implements Cache_IStore_1
	{
		/**
		 * @var Memcache
		 */
		protected $memcache;

		/**
		 */
		public function __construct()
		{
			$this->memcache = new Memcache();
		}

		/**
		 * Adds a server to the pool.
		 * This can be used to spread keys across multiple servers.
		 * @param string $hostname
		 * @param int $port
		 * @param int $weight
		 * @return void
		 */
		public function addServer($hostname, $port = 11211, $weight = 1)
		{
			$this->memcache->addServer($hostname, $port, FALSE, $weight);
		}

		/**
		 * Returns a single item from the store.
		 * @param string $key
		 * @return mixed
		 */
		public function get($key)
		{
			if (($value = $this->memcache->get($key)) !== FALSE)
			{
				return $value;
			}
			return NULL;
		}

		/**
		 * Sets a single item in the store.
		 * @param string $key
		 * @param mixed $value
		 * @param int $ttl
		 * @param int $flag
		 * @return bool
		 */
		public function put($key, $value, $ttl = NULL, $flag = 0)
		{
			return $this->memcache->set($key, $value, $flag, $ttl);
		}

		/**
		 * Get cache statistics.
		 * @param string $type
		 * @return mixed
		 */
		public function getStats($type = NULL)
		{
			if ($type === NULL)
			{
				return $this->memcache->getExtendedStats();
			}
			else
			{
				return $this->memcache->getExtendedStats($type);
			}
		}
	}

?>