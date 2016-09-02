<?php

	/**
	 * @package Security
	 */

	/**
	 * Provides transparent encryption for ArrayAccess objects
	 * Decorates an ArrayAccess object to provide transparent encryption
	 * and decryption on a specific list of keys.
	 * @example examples/crypt_array.php
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Security_CryptArrayDecorator_1 extends Object_1 implements ArrayAccess, IteratorAggregate
	{
		/**
		 * Underlying array that will hold the values
		 * @var ArrayAccess
		 */
		protected $array;

		/**
		 * @var Security_Crypt_1
		 */
		protected $crypt;

		/**
		 * Array of field names that should be encrypted
		 * @var array
		 */
		protected $encrypted_keys;

		/**
		 * Cache of decrypted values
		 *
		 * @var unknown_type
		 */
		protected $cache = array();

		/**
		 * @param Security_Crypt_1 $crypt
		 * @param ArrayAccess $array
		 * @param array $encrypted_keys Array containing the keys that should be encrypted
		 */
		public function __construct(Security_Crypt_1 $crypt, ArrayAccess $array, array $encrypted_keys)
		{
			$this->array = $array;
			$this->crypt = $crypt;
			$this->encrypted_keys = $encrypted_keys;
		}

		/**
		 * Provides access to the underlying array
		 * @return ArrayAccess
		 */
		public function getArray()
		{
			return $this->array;
		}

		/**
		 * Returns a specified offset; if that offset has been marked
		 * for encryption, it will be returned decrypted.
		 *
		 * @param mixed $offset
		 * @return mixed
		 */
		public function offsetGet($offset)
		{
			if (in_array($offset, $this->encrypted_keys))
			{
				if (isset($this->cache[$offset]))
				{
					return $this->cache[$offset];
				}
				return $this->cache[$offset] = $this->crypt->decrypt($this->array->offsetGet($offset));
			}

			return $this->array->offsetGet($offset);
		}

		/**
		 * Explicitly returns the encrypted value of an offset
		 * If the requested offset was marked for encryption, the underlying
		 * value is returned untouched; otherwise, the value is encrypted
		 * before being returned.
		 *
		 * @param mixed $offset
		 * @return mixed
		 */
		public function offsetGetEncrypted($offset)
		{
			if (in_array($offset, $this->encrypted_keys))
			{
				return $this->array->offsetGet($offset);
			}

			return $this->crypt->encrypt($this->array->offsetGet($offset));
		}

		/**
		 * Sets a specified offset; if that offset has been marked
		 * for encryption, it will be encrypted before storage.
		 *
		 * @param mixed $offset
		 * @param mixed $value
		 * @return mixed
		 */
		public function offsetSet($offset, $value)
		{
			// check to see if this field needs to be encrypted
			// offset of NULL is appending
			if (($offset !== NULL)
				&& in_array($offset, $this->encrypted_keys))
			{
				$this->cache[$offset] = $value;
				$value = $this->crypt->encrypt($value);
			}
			return $this->array->offsetSet($offset, $value);
		}

		/**
		 * Explicitly sets a specified offset to an already encrypted value
		 * If the given offset has been marked for encryption, the provided
		 * value is stored untouched; otherwise, the value is decrypted
		 * prior to storing it. Note that, in the latter case, the value given
		 * is assumed to have been encrypted with the same crypt object (or
		 * one with compatible parameters) to the one we're using locally.
		 *
		 * @param mixed $offset
		 * @param mixed $value
		 * @return mixed
		 */
		public function offsetSetEncrypted($offset, $value)
		{
			if ($offset === NULL
				|| !in_array($offset, $this->encrypted_keys))
			{
				$value = $this->crypt->decrypt($value);
			}
			return $this->array->offsetSet($offset, $value);
		}

		/**
		 * Returns whether an offset exists; this is simply
		 * forwarded to the underlying ArrayAccess object
		 *
		 * @param mixed $offset
		 * @return bool
		 */
		public function offsetExists($offset)
		{
			return $this->array->offsetExists($offset);
		}

		/**
		 * Unsets an offset; this is simply forwarded to the
		 * underlying ArrayAccess object
		 *
		 * @param mixed $offset
		 * @return bool
		 */
		public function offsetUnset($offset)
		{
			return $this->array->offsetUnset($offset);
		}

		/**
		 * Returns an iterator that will decrypt values as it iterates
		 *
		 * @return Security_CryptIteratorDecorator_1
		 */
		public function getIterator()
		{
			// properly handle both internal and external iterators
			if ($this->array instanceof IteratorAggregate)
			{
				$iterator = $this->array->getIterator();
			}
			elseif ($this->array instanceof Iterator)
			{
				$iterator = $this->array;
			}
			else
			{
				throw new Exception('Array does not support iteration');
			}

			return new Security_CryptIteratorDecorator_1(
				$this->crypt,
				$iterator,
				$this->encrypted_keys
			);
		}
	}

?>
