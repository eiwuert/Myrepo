<?php

	/**
	 * Allows you to store data keyed by an object instance
	 * i.e., this allows you to store abstract data with an instance,
	 * almost as if they were members of that class
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 * @example examples/object_storage.php
	 */
	class Util_ObjectStorage_1 implements ArrayAccess
	{
		/**
		 * @var array
		 */
		protected $storage = array();

		/**
		 * Returns the data at $offset
		 *
		 * @param object $offset
		 * @return mixed
		 */
		public function offsetGet($offset)
		{
			if (!is_object($offset))
			{
				throw new InvalidArgumentException('Offset must be an object');
			}

			$hash = spl_object_hash($offset);

			return isset($this->storage[$hash])
				? $this->storage[$hash]
				: NULL;
		}

		/**
		 * Sets the data at $offset
		 *
		 * @param object $offset
		 * @param mixed $value
		 * @return void
		 */
		public function offsetSet($offset, $value)
		{
			if (!is_object($offset))
			{
				throw new InvalidArgumentException('Offset must be an object');
			}

			$hash = spl_object_hash($offset);
			$this->storage[$hash] = $value;
		}

		/**
		 * Indicates whether anything exists at $offset
		 *
		 * @param object $offset
		 * @return bool
		 */
		public function offsetExists($offset)
		{
			if (!is_object($offset))
			{
				throw new InvalidArgumentException('Offset must be an object');
			}

			$hash = spl_object_hash($offset);
			return isset($this->storage[$hash]);
		}

		/**
		 * Unsets the data at $offset
		 *
		 * @param object $offset
		 * @return void
		 */
		public function offsetUnset($offset)
		{
			if (!is_object($offset))
			{
				throw new InvalidArgumentException('Offset must be an object');
			}

			$hash = spl_object_hash($offset);
			unset($this->storage[$hash]);
		}
	}

?>
