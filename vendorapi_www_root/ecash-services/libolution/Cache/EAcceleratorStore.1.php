<?php
	/**
	 * @package Cache
	 */

	/**
	 * A cache store that uses the eAccelerator shared memory segment
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Cache_EAcceleratorStore_1 implements Cache_IStore_1
	{
		/**
		 * Returns a single item from the store.
		 * @param string $key
		 * @return mixed
		 */
		public function get($key)
		{
			if (($value = eaccelerator_get($key)) !== FALSE)
			{
				return unserialize($value);
			}
			return NULL;
		}

		/**
		 * Sets a single item in the store.
		 * @param string $key
		 * @param mixed $value
		 * @param int $ttl
		 * @return void
		 */
		public function put($key, $value, $ttl = NULL)
		{
			eaccelerator_put($key, serialize($value), $ttl);
		}
	}

?>
