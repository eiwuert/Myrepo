<?php
	/**
	 * @package Unix
	 */

	/**
	 * Mostly contains class constants common to the queue client and master,
	 * but also sets up access to the shared memory segment and the message
	 * queue used by the queue master
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 *
	 */
	abstract class Unix_WorkQueue_1 extends Object_1
	{
		const MSG_DATA_READY = 300;
		const MSG_WORK_WAITING = 200;
		const MSG_WORK_ITEM_FINISHED = 210;
		const MSG_ADD_WORK_ITEM = 1000;
		const MSG_WORK_ITEM_WAITING = 1001;
		const MSG_REPORT_XFER = 2000;
		/**
		 * Worker has spawned
		 */
		const MSG_WORKER_ALIVE = 100;

		/**
		 * Worker has terminated
		 */
		const MSG_WORKER_EXIT = 110;

		/**
		 * Worker is idle
		 */
		const MSG_WORKER_SLEEPING = 120;

		/**
		 * Worker is busy
		 */
		const MSG_WORKER_BUSY = 130;

		/**
		 * @var MessageQueue
		 */
		protected $queue;

		/**
		 * @var Unix_SharedMemory_1
		 */
		protected $shared_mem;

		/**
		 * @param Unix_IPCKey_1 $key
		 */
		public function __construct(Unix_IPCKey_1 $key)
		{
			$this->queue = $this->getMessageQueue($key);
			$this->shared_mem = new Unix_SharedMemory_1($key);
		}

		public function remove()
		{
			$this->shared_mem->remove();
		}

		/**
		 * Instantiates a work queue
		 *
		 * @param Unix_IPCKey_1 $ipc_key
		 * @return Unix_MessageQueue_1
		 */
		protected function getMessageQueue(Unix_IPCKey_1 $ipc_key)
		{
			return new Unix_MessageQueue_1($ipc_key);
		}
	}
