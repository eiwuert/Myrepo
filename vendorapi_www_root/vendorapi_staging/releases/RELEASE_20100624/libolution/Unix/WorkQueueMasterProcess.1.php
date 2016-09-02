<?php
	/**
	 * @package Unix
	 */

	/**
	 * Represents a process which spawns other (worker) processes
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class Unix_WorkQueueMasterProcess_1 extends Unix_ForkedParentProcess_1
	{
		/**
		 * @var Unix_WorkQueueMaster_1
		 */
		protected $work_queue;

		/**
		 * @param Unix_IPCKey_1 $ipc_key key to use when setting up IPC
		 * @param int $worker_count Number of workers
		 * @param int $sleep_time Number of microseconds to sleep between each tick
		 */
		public function __construct(Unix_IPCKey_1 $ipc_key, $worker_count = 5, $sleep_time = 100000)
		{
			parent::__construct($ipc_key, $worker_count, $sleep_time);
			$this->work_queue = new Unix_WorkQueueMaster_1($ipc_key);
		}

		/**
		 * @return Unix_WorkQueueMaster_1
		 */
		public function getWorkQueue()
		{
			return $this->work_queue;
		}

		/**
		 * @param Unix_IPCKey_1 $key
		 * @return Unix_ForkedWorkerProcess_1
		 */
		public function workerFactory(Unix_IPCKey_1 $key)
		{
			return new Unix_WorkQueueClientProcess_1($key);
		}

		/**
		 * Called when the process is ending
		 */
		public function onExit()
		{
			parent::onExit();
			$this->work_queue->remove();
		}

		/**
		 * Called each cycle
		 */
		protected function tick()
		{
			parent::tick();
			$this->work_queue->update();
		}
	}
?>