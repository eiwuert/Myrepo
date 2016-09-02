<?php

	/**
	 * Iterates an iterator, decrypting values as necessary
	 * Note: to use a standard array with this class, you will need to wrap it
	 * in an ArrayIterator object (included in SPL).
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Security_CryptIteratorDecorator_1 implements Iterator
	{
		/**
		 * @var Security_Crypt_1
		 */
		protected $crypt;

		/**
		 * @var Iterator
		 */
		protected $iterator;

		/**
		 * @var array
		 */
		protected $encrypted_keys;

		/**
		 * Holds the current value for efficiency
		 *
		 * @var mixed
		 */
		protected $current = NULL;

		/**
		 * Holds the current key, again for efficiency
		 *
		 * @var mixed
		 */
		protected $key = NULL;

		/**
		 * Indicates whether we're on a valid item
		 *
		 * @var bool
		 */
		protected $valid = FALSE;

		/**
		 * @param Security_Crypt_1 $crypt
		 * @param Iterator $iterator
		 * @param array $encrypted_keys Array containing the keys that should be encrypted
		 */
		public function __construct(Security_Crypt_1 $crypt, Iterator $iterator, array $encrypted_keys)
		{
			$this->crypt = $crypt;
			$this->iterator = $iterator;
			$this->encrypted_keys = $encrypted_keys;
		}

		/**
		 * Rewinds to the first element; required by Iterator
		 * @return mixed
		 */
		public function rewind()
		{
			$this->iterator->rewind();
			$this->updateCurrent();

			return $this->current;
		}

		/**
		 * Returns the current item; required by Iterator
		 * @return mixed
		 */
		public function current()
		{
			return $this->current;
		}

		/**
		 * Returns the current key; required by Iterator
		 * @return mixed
		 */
		public function key()
		{
			return $this->key;
		}

		/**
		 * Check if there is a current item after calls to rewind() or next(); required by Iterator
		 * @return mixed
		 */
		public function valid()
		{
			return $this->valid;
		}

		/**
		 * Move forward to the next element; required by Iterator
		 * @return mixed
		 */
		public function next()
		{
			$this->iterator->next();
			$this->updateCurrent();

			return $this->current;
		}

		/**
		 * Updates the internal status to reflect changes
		 * @return void
		 */
		protected function updateCurrent()
		{
			$this->valid = $this->iterator->valid();

			if ($this->valid)
			{
				$this->current = $this->iterator->current();
				$this->key = $this->iterator->key();

				// if we have an item and it's encrypted, decrypt it
				if (in_array($this->key, $this->encrypted_keys))
				{
					$this->current = $this->crypt->decrypt($this->current);
				}
			}
			else
			{
				$this->current = NULL;
				$this->key = NULL;
			}
		}
	}

?>
