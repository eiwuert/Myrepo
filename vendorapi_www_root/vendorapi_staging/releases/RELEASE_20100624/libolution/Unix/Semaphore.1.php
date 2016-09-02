<?php
	/**
	 * @package Unix
	 */

	/**
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 *
	 */
	class Unix_Semaphore_1 extends Object_1
	{
		/**
		 * semaphore id returned from sem_get()
		 *
		 * @var int
		 */
		private $semaphore_id;

		/**
		 * ipc key used to identify this semaphore
		 *
		 * @var int
		 */
		private $ipc_key;

		/**
		 * @param Unix_IPCKey_1 $key
		 * @param int $max_acquire Max number of concurrent acquires allowed
		 */
		public function __construct(Unix_IPCKey_1 $key, $max_acquire = 1)
		{
			$this->ipc_key = $key;
			$this->semaphore_id = sem_get($this->ipc_key->getKey(), $max_acquire, 0644, FALSE);
		}

		/**
		 * Attempts to acquire the lock.  Blocks until success. Throws SemaphoreException on
		 * failure.
		 */
		public function acquire()
		{
			if (!sem_acquire($this->semaphore_id))
			{
				throw new Unix_SemaphoreException_1("Unable to acquire lock.");
			}
			return TRUE;
		}

		/**
		 * Attempts to release the lock. If a lock has not been acquired by our process, or there
		 * was some other error, an exception is thrown.
		 */
		public function release()
		{
			if (!sem_release($this->semaphore_id))
			{
				throw new Unix_SemaphoreException_1("Unable to release lock. Perhaps it was not acquired first?");
			}
			return TRUE;
		}

		/**
		 * Destroy the semaphore
		 *
		 */
		public function remove()
		{
			if ($this->semaphore_id !== NULL)
			{
				if (!sem_remove($this->semaphore_id))
				{
					throw new Unix_SemaphoreException_1('Unable to remove the semaphore');
				}
				$this->semaphore_id = NULL;
			}
		}
	}
?>