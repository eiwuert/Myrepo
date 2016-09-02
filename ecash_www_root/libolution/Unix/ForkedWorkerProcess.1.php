<?php
	/**
	 * @package Unix
	 */

	/**
	 * Class representing a single worker process
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class Unix_ForkedWorkerProcess_1 extends Unix_ForkedProcess_1
	{
		/**
		 * @var Unix_MessageQueue_1
		 */
		protected $message_queue;

		/**
		 * @var Unix_IPCKey_1
		 */
		protected $ipc_key;

		/**
		 * @param Unix_IPCKey_1 $message_queue_key
		 * @param int $sleep_time
		 */
		public function __construct(Unix_IPCKey_1 $ipc_key, $sleep_time = 100000)
		{
			parent::__construct($sleep_time);
			$this->message_queue = $this->getMessageQueue($ipc_key);
			$this->ipc_key = $ipc_key;
		}

		/**
		 * Called by parent class after having forked
		 *
		 */
		public function onStartup()
		{
			//$this->log("Child alive.");
		}

		/**
		 * Called by parent class just before exit
		 *
		 */
		public function onExit()
		{
			//$this->log("Exiting");
		}

		/**
		 * Called by parent class regularly
		 *
		 */
		public function tick()
		{
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
?>