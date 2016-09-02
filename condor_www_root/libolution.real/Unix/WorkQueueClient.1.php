<?php

	/**
	 * Class for communicating with a master work queue (Unix_WorkQueueMaster_1)
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 *
	 */
	class Unix_WorkQueueClient_1 extends Unix_WorkQueue_1
	{
		/**
		 * Notifies the master queue of new work that needs to be done.
		 *
		 * @param Unix_WorkItem_1 $item
		 */
		public function addWork(Unix_WorkItem_1 $item)
		{
			$this->queue->sendPackage(self::MSG_ADD_WORK_ITEM, $item);
		}

		/**
		 * Executes a work item, if one is available.  Will return immediately if
		 * no work is available. Does not block. Returns TRUE if work was performed,
		 * FALSE is no work was performed, and an Exception if there was a problem
		 * executing a work item
		 *
		 * @return bool
		 */
		public function doWork()
		{
			if (($msg = $this->queue->receivePackageQuick(self::MSG_WORK_ITEM_WAITING)) != FALSE)
			{
				if ($msg instanceof Unix_WorkItem_1)
				{
					$this->reportBusy();
					$msg->setWorkQueueClient($this);
					$msg->setSharedMemorySegment($this->shared_mem);
					$msg->execute();
					$this->reportWorkFinished($msg);
					$this->reportIdle();
					return TRUE;
				}
			}
			return FALSE;
		}

		/**
		 * Notifies master work queue that this client is busy
		 *
		 */
		public function reportBusy()
		{
			$this->queue->send(self::MSG_WORKER_BUSY, posix_getpid());
		}

		/**
		 * Notifies master work queue that this client is idle
		 *
		 */
		public function reportIdle()
		{
			$this->queue->send(self::MSG_WORKER_SLEEPING, posix_getpid());
		}

		/**
		 * Notifies master work queue that this client has finished its current task
		 *
		 */
		public function reportWorkFinished(Unix_WorkItem_1 $item)
		{
			$this->queue->sendPackage(self::MSG_WORK_ITEM_FINISHED, $item);
		}

		/**
		 * Notifies master work queue that this client has been created
		 *
		 */
		public function reportAlive()
		{
			$this->queue->send(self::MSG_WORKER_ALIVE, posix_getpid());
		}
	}

?>