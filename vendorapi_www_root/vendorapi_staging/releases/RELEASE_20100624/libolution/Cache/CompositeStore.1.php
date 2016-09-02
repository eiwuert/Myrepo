<?php
	/**
	 * @package Cache
	 */

	/**
	 * Allows you to treat a collection of stores as a single store
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Cache_CompositeStore_1 extends Collections_List_1 implements Cache_IStore_1
	{
		/**
		 * Adds a store to our composite
		 *
		 * @param int $offset
		 * @param Cache_IStore $value
		 */
		public function offsetSet($offset, $value)
		{
			if (!$value instanceof Cache_IStore_1)
			{
				throw new InvalidParameterException('Value must be an instance of Cache_IStore');
			}
			$this->stores[] = $value;
		}

		/**
		 * Searches all stores until the key is found
		 *
		 * @param string $key
		 * @return mixed
		 */
		public function get($key)
		{
			$value = NULL;

			foreach ($this->items as $store)
			{
				// first one that returns a value wins!
				if (($value = $store->get($key)) !== NULL)
				{
					break;
				}
			}

			return $value;
		}

		/**
		 * Sets the value for the given key in all stores
		 *
		 * @param string $key
		 * @param mixed $value
		 * @param int $ttl
		 */
		public function put($key, $value, $ttl = NULL)
		{
			foreach ($this->stores as $store)
			{
				try
				{
					$store->put($key, $value, $ttl);
				}
				catch (Exception $e)
				{
					// gulp
				}
			}
		}
	}

?>
