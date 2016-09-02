<?php
	/**
	 * @package Unix
	 */

	/**
	 * abstract base class for all work items.  Child classes are expected
	 * to implement execute() which will be called by the WorkQueueClient class.
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	abstract class Unix_WorkItem_1 extends Object_1
	{
		/**
		 * @var Unix_WorkQueueClient_1
		 */
		protected $work_queue_client;

		/**
		 * @var Unix_SharedMemory_1
		 */
		protected $shared_memory;

		/**
		 * @var int
		 */
		protected $result_shm_key;

		/**
		 * @var mixed
		 */
		protected $result;


		/**
		 * Stores a reference to the work queue client for this process in the class
		 * so child WorkItems may queue additional work if need be
		 *
		 * @param Unix_WorkQueueClient_1 $work_queue_client
		 */
		public function setWorkQueueClient(Unix_WorkQueueClient_1 $work_queue_client)
		{
			$this->work_queue_client = $work_queue_client;
		}

		/**
		 * Store a reference to the shared memory segment for passing results
		 *
		 * @param Unix_SharedMemory_1 $shared_mem
		 */
		public function setSharedMemorySegment(Unix_SharedMemory_1 $shared_mem)
		{
			$this->shared_memory = $shared_mem;
		}

		/**
		 * Sets the result data for this work item.
		 *
		 * @param mixed $data
		 */
		protected function setResult($data)
		{
			if ($this->shared_memory === NULL)
			{
				$this->result = $data;
			}
			else
			{
				$this->result_shm_key = $this->shared_memory->generateKey();
				$this->shared_memory->set($this->result_shm_key, $data);
			}
		}

		/**
		 * Fetches the result for this work item.  Deletes the result before returning it
		 * unless otherwise specified.
		 *
		 * @param bool $auto_free
		 */
		public function getResult($auto_free = TRUE)
		{
			if ($this->shared_memory === NULL || $this->result_shm_key === NULL)
			{
				$data = $this->result;
			}
			else
			{
				$data = $this->shared_memory->get($this->result_shm_key);
			}

			if ($auto_free)
			{
				$this->freeResult();
			}

			return $data;
		}

		/**
		 * Free the result and delete the shared memory segment
		 *
		 */
		public function freeResult()
		{
			$this->result = NULL;
			if ($this->result_shm_key !== NULL)
			{
				$this->shared_memory->delete($this->result_shm_key);
			}
		}

		/**
		 * This is your "do stuff" method.  Implement this method with the code you
		 * wish to be executed by a child worker.
		 *
		 */
		public abstract function execute();
	}

