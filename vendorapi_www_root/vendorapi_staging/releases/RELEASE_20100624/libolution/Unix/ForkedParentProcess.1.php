<?php
	/**
	 * @package Unix
	 */

	/**
	 * Represents a process which spawns other (worker) processes
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class Unix_ForkedParentProcess_1 extends Unix_ForkedProcess_1
	{
		/**
		 * Max number of workers to maintain
		 * @var int
		 */
		protected $worker_count;

		/**
		 * PIDs of our child workers
		 * @var array
		 */
		protected $children = array();

		/**
		 * @var Unix_MessageQueue_1
		 */
		protected $message_queue;

		/**
		 * @var Unix_IPCKey_1
		 */
		protected $ipc_key;

		/**
		 * @param Unix_IPCKey_1 $ipc_key key to use when setting up IPC
		 * @param int $worker_count Number of workers
		 * @param int $sleep_time Number of microseconds to sleep between each tick
		 */
		public function __construct(Unix_IPCKey_1 $ipc_key, $worker_count = 5, $sleep_time = 100000)
		{
			parent::__construct($sleep_time);
			$this->worker_count = $worker_count;
			$this->ipc_key = $ipc_key;
			$this->message_queue = $this->getMessageQueue($ipc_key);
		}

		/**
		 * Called when the process is exiting
		 */
		public function onExit()
		{
			$this->killChildren();

			foreach ($this->children as $child)
			{
				//$this->log("Reaping $child");
				if (($return_code = pcntl_waitpid($child, $status, WUNTRACED)))
				{
					//$this->log("reaped.");
				}
			}

			$this->message_queue->remove();
		}

		/**
		 * Called when the process has just started
		 */
		public function onStartup()
		{
			//$this->log("Alive");

			while (count($this->children) < $this->worker_count)
			{
				$worker = $this->workerFactory($this->ipc_key);
				$pid = $worker->fork(FALSE);
				$this->children[$pid] = $pid;
			}

			$this->enableSignal(SIGCHLD);
		}

		/**
		 * @param Unix_IPCKey_1 $key
		 * @return Unix_ForkedWorkerProcess_1
		 */
		public function workerFactory(Unix_IPCKey_1 $key)
		{
			return new Unix_ForkedWorkerProcess_1($key);
		}

		/**
		 * Issues SIGTERM to all workers.
		 * NOTE: Killing children is habit forming and should be avoided.
		 */
		protected function killChildren()
		{
			foreach ($this->children as $child_pid)
			{
				//$this->log("Sending SIGTERM to child ".$child_pid);
				posix_kill($child_pid, SIGTERM);
			}
		}

		/**
		 * Find any exited workers and clean them up
		 *
		 */
		protected function reapChildren()
		{
			$status = NULL;
			while (count($this->children) && ($return_code = pcntl_wait($status, WNOHANG | WUNTRACED)) != FALSE)
			{
				if ($return_code == -1)
				{
					throw new Exception("Something has gone very wrong.");
				}
				//$this->log("reaping child " . $return_code);
				unset($this->children[$return_code]);
			}
		}

		/**
		 * Called each cycle
		 */
		protected function tick()
		{
			if ($this->hasSignal(SIGCHLD))
			{
				$this->reapChildren();
			}
		}

		/**
		 * Instantiates a work queue
		 *
		 * @param Unix_IPCKey_1 $ipc_key
		 * @return Unix_MessageQueue_1
		 */
		protected function getMessageQueue(Unix_IPCKey_1 $ipc_key)
		{
			return new Unix_MessageQueue_1($ipc_key, TRUE);
		}
	}
?>