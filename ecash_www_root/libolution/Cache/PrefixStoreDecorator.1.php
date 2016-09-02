<?php
	/**
	 * @package Cache
	 */

	/**
	 * Allows you to artificially segregate a store by prefixing all keys
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Cache_PrefixStoreDecorator_1 implements Cache_IStore_1
	{
		/**
		 * @param Cache_IStore $store
		 * @param string $prefix
		 */
		public function __construct(Cache_IStore_1 $store, $prefix)
		{
			$this->store = $store;
			$this->prefix = $prefix;
		}

		/**
		 * Gets the value for a key, with our prefix
		 *
		 * @param string $key
		 * @return mixed
		 */
		public function get($key)
		{
			return $this->store->get($this->prefix.$key);
		}

		/**
		 * Sets a value for a key, with our prefix
		 *
		 * @param string $key
		 * @param mixed $value
		 * @param int $ttl
		 * @return void
		 */
		public function put($key, $value, $ttl = NULL)
		{
			return $this->store->put($this->prefix.$key, $value, $ttl);
		}
	}

?>
