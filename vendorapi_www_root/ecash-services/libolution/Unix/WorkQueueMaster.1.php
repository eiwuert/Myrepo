<?php
	/**
	 * @package Unix
	 */

	/**
	 * Master work queue.  This class holds any pending work items, and only
	 * exists in the parent process. Worker processes communicate with this object
	 * via Unix_WorkQueueClient_1.
	 * 
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 *
	 */
	class Unix_WorkQueueMaster_1 extends Unix_WorkQueue_1
	{
		/**
		 * queue of items waiting to be handled
		 *
		 * @var array
		 */
		private $items = array();
		
		/**
		 * number of idle worker clients
		 *
		 * @var int
		 */
		private $idle_workers = 0;
		
		/**
		 * number of items that have been sent to workers but have not yet completed
		 *
		 * @var int
		 */
		private $pending_work_items = 0;
		
		/**
		 * @var Event_1
		 */
		public $OnWorkFinished;
		
		/**
		 * @param Unix_IPCKey_1 $key
		 */
		public function __construct(Unix_IPCKey_1 $key)
		{
			parent::__construct($key);
			$this->OnWorkFinished = new Event_1();
		}
		
		/**
		 * Property that tells if there are unfinished items
		 *
		 * @return bool
		 */
		public function getHasIncompleteItems()
		{
			return ($this->pending_work_items > 0 || count($this->items) > 0);
		}
		
		/**
		 * returns the number of items that have been dispatched but are incomplete
		 *
		 * @return int
		 */
		public function getDispatchedItemCount()
		{
			return ($this->pending_work_items);
		}
		
		/**
		 * returns the number of items that are waiting to be dispatched.
		 *
		 * @return int
		 */
		public function getQueuedItemCount()
		{
			return (count($this->items));
		}
		
		/**
		 * returns the total number of items being processed
		 *
		 */
		public function getCount()
		{
			return (count($this->items) + $this->pending_work_items);
		}

		/**
		 * This needs to be called periodically.  Usually in a tick() in a ForkedProcess.
		 *
		 */
		public function update()
		{
			while ($this->queue->receiveQuick(self::MSG_WORKER_ALIVE))
			{
			}
			
			while ($this->queue->receiveQuick(self::MSG_WORKER_SLEEPING))
			{
				$this->idle_workers++;
			}
			
			while ($item = $this->queue->receivePackageQuick(self::MSG_WORK_ITEM_FINISHED))
			{
				$item->setSharedMemorySegment($this->shared_mem);
				$this->OnWorkFinished->invoke($item);
				$this->pending_work_items--;
			}
			
			while ($this->queue->receiveQuick(self::MSG_WORKER_BUSY))
			{
				$this->idle_workers--;
			}
			
			while ($this->queue->receiveQuick(self::MSG_WORKER_EXIT))
			{
				$this->idle_workers--;
			}

			while ($this->idle_workers - $this->pending_work_items > 0 && sizeof($this->items) > 0)
			{
				$this->pending_work_items++;
				$this->queue->sendPackage(self::MSG_WORK_ITEM_WAITING, array_shift($this->items));
			}

			while ($item = $this->queue->receivePackageQuick(self::MSG_ADD_WORK_ITEM))
			{
				$this->addWork($item);
			}
		}

		/**
		 * Adds a work item to the master queue. direct operation
		 *
		 * @param WorkItem $item
		 */
		public function addWork(Unix_WorkItem_1 $item)
		{
			$this->items[] = $item;
		}
	
	}
