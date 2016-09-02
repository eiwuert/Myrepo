<?php

	/**
	 * @package Cache
	 */

	/**
	 * A unified interface for accessing various caching stores, such as
	 * shared memory, memcached, etc.
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	interface Cache_IStore_1
	{
		/**
		 * Returns a single item from the store. If the item is
		 * not found, returns NULL
		 * @param string $key
		 * @return mixed value
		 */
		public function get($key);

		/**
		 * Puts a single item into the store. If ttl is provided,
		 * the item should live in the cache for that many seconds.
		 * @param string $key
		 * @param mixed $value
		 * @param int $ttl
		 * @return void
		 */
		public function put($key, $value, $ttl = NULL);
	}

?>
