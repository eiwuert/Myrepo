<?php

	/**
	 * @package Collections
	 */

	/**
	 * A generic collection list
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class Collections_List_1 extends Object_1 implements ArrayAccess, Iterator, Countable
	{
		/**
		 * @var array
		 */
		protected $items = array();

		/**
		 * Returns the item at the given offset
		 *
		 * @param mixed $offset
		 * @return mixed
		 */
		public function offsetGet($offset)
		{
			return $this->items[$offset];
		}

		/**
		 * Sets the item at the given offset
		 * If offset is null, the item is added to the end of the list
		 *
		 * @param mixed $offset
		 * @param mixed $value
		 * @return void
		 */
		public function offsetSet($offset, $value)
		{
			if ($offset !== NULL)
			{
				$this->items[$offset] = $value;
			}
			else
			{
				$this->items[] = $value;
			}
		}

		/**
		 * Removes the item at the given offset from the list
		 *
		 * @param mixed $offset
		 * @return void
		 */
		public function offsetUnset($offset)
		{
			unset($this->items[$offset]);
		}

		/**
		 * Indicates whether an item exists at the given offset
		 *
		 * @param mixed $offset
		 * @return bool
		 */
		public function offsetExists($offset)
		{
			return isset($this->items[$offset]);
		}

		/**
		 * Returns the number of items in the list
		 *
		 * @return int
		 */
		public function count()
		{
			return count($this->items);
		}

		/**
		 * Rewinds the internal iterator
		 *
		 * @return mixed
		 */
		public function rewind()
		{
			return reset($this->items);
		}

		/**
		 * Returns the item at the current position of the internal iterator
		 *
		 * @return mixed
		 */
		public function current()
		{
			return current($this->items);
		}

		/**
		 * Returns the offset at the current position of the internal iterator
		 *
		 * @return mixed
		 */
		public function key()
		{
			return key($this->items);
		}

		/**
		 * Advances the internal interator
		 *
		 * @return mixed
		 */
		public function next()
		{
			return next($this->items);
		}

		/**
		 * Indicates whether the list contains more items
		 *
		 * @return bool
		 */
		public function valid()
		{
			return ($this->key() !== NULL);
		}

		/**
		 * Adds an item to the end of the list
		 *
		 * @param mixed $item
		 * @return void
		 */
		public function add($item)
		{
			$this->offsetSet(NULL, $item);
		}

		/**
		 * Clears the list of all items
		 * @return void
		 */
		public function clear()
		{
			$this->items = array();
		}
	}

?>
