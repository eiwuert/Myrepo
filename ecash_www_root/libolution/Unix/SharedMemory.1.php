<?php
	/**
	 * @package Unix
	 */

	/**
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 *
	 */
	class Unix_SharedMemory_1 extends Object_1 implements Unix_ISharedMemoryAccessor_1
	{
		/**
		 * Default shared memory size is 16mb
		 *
		 */
		const DEFAULT_SEGMENT_SIZE = 16777216;

		/**
		 * @var Unix_IPCKey_1
		 */
		private $ipc_key = NULL;

		/**
		 * @var Unix_Semaphore_1
		 */
		private $semaphore = NULL;

		/**
		 * @var int
		 */
		private $segment_size;

		/**
		 * @var resource
		 */
		private $segment_id = NULL;

		/**
		 * @var int
		 */
		private $key_incrementor = 0;

		/**
		 * Instanciates a new shared memory object.  If the segment does not exist, it will be created.
		 * If a segment already exists for this IPC Key, we will attach to the existing one. However,
		 * it may be higher performance to only instanciate one of these objects per thread.
		 *
		 * @param Unix_IPCKey_1 $key
		 * @param int $segment_size
		 */
		public function __construct(Unix_IPCKey_1 $key, $segment_size = self::DEFAULT_SEGMENT_SIZE)
		{
			$this->ipc_key = $key;
			$this->segment_size = $segment_size;

			$this->segment_id = shm_attach($this->ipc_key->getKey(), $this->segment_size);
			$this->semaphore = new Unix_Semaphore_1($key);
		}

		/**
		 * Retrieves an item from the shared memory segment.  Internally manages thread-safety at a
		 * very strict isolation level. Throws an exception upon failure.  Data is returned upon success.
		 *
		 * Key must be an integer.  You may specify any integer, and is up to you to keep track of these keys.
		 * Additionally, there is a helper function which can generate semi-unique keys for you.  It is not
		 * considered very reliable for high concurrency, but may work well for most applications. The method
		 * in question is generateKey().
		 *
		 * @param int $key
		 * @return
		 */
		public function get($key)
		{
			$this->semaphore->acquire();
			$data = @shm_get_var($this->segment_id, $key);
			$this->semaphore->release();

			if ($data != TRUE)
			{
				throw new Unix_SharedMemoryException_1("Could not retrieve data.");
			}
			return unserialize($data);
		}

		/**
		 * Stores an item in the shared memory segment. Internally manages thread-safety at a
		 * very strict isolation level.  Throws an exception on failure, otherwise returns nothing.
		 *
		 * Key must be an integer.  You may specify any integer, and is up to you to keep track of these keys.
		 * Additionally, there is a helper function which can generate semi-unique keys for you.  It is not
		 * considered very reliable for high concurrency, but may work well for most applications. The method
		 * in question is generateKey().
		 *
		 * $data may be any serializable object.  Serialization is handled internally.
		 *
		 * @param int $key
		 * @param mixed $data
		 */
		public function set($key, $data)
		{
			$this->semaphore->acquire();
			$result = @shm_put_var($this->segment_id, $key, serialize($data));
			$this->semaphore->release();

			if ($result == FALSE)
			{
				throw new Unix_SharedMemoryException_1("Could not store data.");
			}
		}

		/**
		 * Deletes an item from the shared memory segment. Internally manages thread-safety.
		 *
		 * Key must be an integer.  You may specify any integer, and is up to you to keep track of these keys.
		 * Additionally, there is a helper function which can generate semi-unique keys for you.  It is not
		 * considered very reliable for high concurrency, but may work well for most applications. The method
		 * in question is generateKey().
		 *
		 * @param int $key
		 */
		public function delete($key)
		{
			$this->semaphore->acquire();
			$result = @shm_remove_var($this->segment_id, $key);
			$this->semaphore->release();

			if ($result == FALSE)
			{
				throw new Unix_SharedMemoryException_1("Could not delete data.");
			}
		}

		/**
		 * Remove the shared memory segment
		 *
		 */
		public function remove()
		{
			if ($this->segment_id !== NULL)
			{
				shm_remove($this->segment_id);
				$this->segment_id = NULL;
				$this->semaphore->remove();
			}
		}

		/**
		 * Returns a (hopefully) unused shared memory key.  This is created using
		 * our process ID and a number incrementor.  Collisions will happen in the following
		 * cases:
		 *
		 *  (a) you call this function 65,000 times and it wraps around to a key you have not freed.
		 *  (b) someone else is using the shared memory segment and not following the rules.
		 *
		 * The key generated is composed such:
		 *
		 *   binary: AAAA AAAA BBBB BBBB
		 *
		 * Where A is our process id and B is our incrementor, starting at 0
		 *
		 * NOTE: This function is not required for use of this class.  You may standardize on your own
		 * keys, so long as you keep track of your keys properly.
		 *
		 * @return int
		 */
		public function generateKey()
		{
			return ((getmypid() << 16) | $this->key_incrementor++);
		}
	}

?>
