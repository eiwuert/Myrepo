<?php
	/**
	 * @package Unix 
	 */

	/**
	 * Class representing a single worker process
	 * 
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class Unix_WorkQueueClientProcess_1 extends Unix_ForkedWorkerProcess_1 
	{
		/**
		 * @var Unix_WorkQueueClient_1
		 */
		protected $work_queue_client = NULL;
		
		/**
		 * @param Unix_IPCKey_1 $message_queue_key
		 * @param int $sleep_time
		 */
		public function __construct($ipc_key, $sleep_time = 100000)
		{
			parent::__construct($ipc_key, $sleep_time);
			$this->work_queue_client = new Unix_WorkQueueClient_1($ipc_key);
		}
		
		/**
		 * Called by parent class after having forked
		 *
		 */
		public function onStartup()
		{
			parent::onStartup();
			$this->work_queue_client->reportAlive();
			$this->work_queue_client->reportIdle();
		}

		/**
		 * Called by parent class regularly
		 *
		 */
		public function tick()
		{
			parent::tick();
			$this->work_queue_client->doWork();
		}
	}
?>