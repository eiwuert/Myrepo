<?php
	/**
	 * Queue Manager
	 *
	 * Manages all ecash queues and queue groups.
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class ECash_Queues_QueueManager extends Object_1 //ECash_Queues_QueueBase
	{
		/**
		 * @var array
		 */
		protected $groups = array();

		/**
		 * @var array
		 */
		protected $queues = array();

		/**
		 * @var array
		 */
		protected $queues_unique = array();

		/**
		 * @var array
		 */
		protected $groups_unique = array();

		/**
		 * @var array
		 */
		protected $queues_by_section_id = array();

		/**
		 * @var array
		 */
		protected $sections_by_queue_id = array();

		/**
		 * @var array
		 */
		protected $queues_by_type = array();

		/**
		 * @var DB_IConnection_1
		 */
		protected $db;

		/**
		 * renew queues upon construction
		 */
		public function __construct(DB_IConnection_1 $db)
		{
			$this->db = $db;
			$this->renewQueues();
		}

		public function renewQueues()
		{
			$groups = ECash::getFactory()->getModel('QueueGroupList', $this->db);
			$groups->loadBy(array());
			$this->groups = array();
			$this->queues = array();
			$this->queues_by_section_id = array();
			$this->sections_by_queue_id = array();
			$this->queues_by_type = array();
			$this->queues_unique = array();

			foreach ($groups as $group)
			{
				if(!isset($this->groups[$group->name_short]))
					$this->groups[$group->name_short] = new ECash_Queues_QueueGroup($group, $this->db);

				if(!isset($this->groups[(int)$group->queue_group_id]))
					$this->groups[(int)$group->queue_group_id] = new ECash_Queues_QueueGroup($group, $this->db);

				$this->groups_unique[(int)$group->queue_group_id] = $this->groups[(int)$group->queue_group_id];
			}

			$queues = ECash::getFactory()->getModel('QueueList', $this->db);
			$queues->loadAvailableQueues(ECash::getCompany()->company_id);

			//this may be getting more than we need, but better to do it in one query
			$queues_to_display = ECash::getFactory()->getModel('QueueDisplayList', $this->db);
			$queues_to_display->loadAll();
			//pull the first display record so we can call 'current()' later on

			$this->sections_by_queue_id = array();

			while($qd = $queues_to_display->next())
			{
				if(!isset($this->sections_by_queue_id[$qd->queue_id]))
				{
					$this->sections_by_queue_id[$qd->queue_id] = array();
				}
				$this->sections_by_queue_id[$qd->queue_id][] = $qd->section_id;
			}

			foreach ($queues as $queue)
			{

				$class = ECash::getFactory()->getClassString($queue->control_class);
				$queue_inst = new $class($queue, $this->db);

				if ($queue_inst instanceof  ECash_Queues_IQueue)
				{
					$this->queues[trim($queue->name_short)] = $queue_inst;
					$this->queues[(int)$queue->queue_id] = $queue_inst;
					$this->queues_unique[trim($queue->name_short)] = $queue_inst;

					if ($queue->queue_group_id !== NULL)
					{
						$this->groups[(int)$queue->queue_group_id]->addQueueRef($queue_inst);
						$this->groups[$this->groups[(int)$queue->queue_group_id]->model->name_short]->addQueueRef($queue_inst);
					}

					if (!isset($this->queues_by_section_id[$queue->section_id]))
					{
						$this->queues_by_section_id[$queue->section_id] = array();
					}
//					if(!isset($this->sections_by_queue_id[$queue->queue_id]))
//					{
//						$this->sections_by_queue_id[$queue->queue_id] = array();
//					}
					//only advance the display iterator a bit at a time (you can't rewind it)
//					$qd = $queues_to_display->current();
//					while($qd->queue_id == $queue->queue_id)
//					{
//						$this->queues_by_section_id[$qd->section_id][] = $queue_inst;
//						$this->sections_by_queue_id[$qd->queue_id][] = $qd->section_id;
//						$qd = $queues_to_display->next();
//					}
					if(!empty($this->sections_by_queue_id[$queue->queue_id]))
					{
						foreach ($this->sections_by_queue_id[$queue->queue_id] as $id_of_queue => $section_to_display)
						{
							$this->queues_by_section_id[$section_to_display][] = $queue_inst;
						}
					}
					if (!isset($this->queues_by_type[$queue->control_class]))
					{
						$this->queues_by_type[$queue->control_class] = array();
					}
					$this->queues_by_type[$queue->control_class][$queue->name_short] = $queue_inst;
				}
				else
				{
					throw new ECash_Queues_QueueException("Class was found but it does not implement ECash_Queues_IQueue");
				}
			}

		}

		/**
		 * @return array
		 */
		public function getQueues()
		{
			return $this->queues_unique;
		}

		/**
		 * @return array
		 */
		public function getQueueGroups()
		{
			return $this->groups_unique;
		}

		/**
		 * This gets all the queues that are supposed to display in a section
		 *
		 * @return ECash_Queues_BasicQueue[]
		 */
		public function getQueuesBySectionId($section_id)
		{
			if (!isset($this->queues_by_section_id[$section_id]))
			{
				return array();
			}
			return $this->queues_by_section_id[$section_id];
		}

		/**
		 * This gets all the sections that a queue is supposed to display in
		 *
		 * @return int[] section ids
		 */
		public function getSectionsByQueueId($queue_id)
		{
			if (!isset($this->sections_by_queue_id[$queue_id]))
			{
				return array();
			}
			return $this->sections_by_queue_id[$queue_id];
		}

		/**
		 * Performs an insert to a specific queue.  However, if this queue is also in a group, it will remove it from
		 * other queues in that group
		 *
		 * @param ECash_Queus_BasicQueueItem $item
		 * @param string $queue_name_short
		 * @param string $date_available
		 */
		public function moveToQueue(ECash_Queues_BasicQueueItem $item, $queue_name_short)
		{
			$queue = $this->getQueue($queue_name_short);
			if ($queue->Model->queue_group_id != NULL)
			{
				$group = $this->getQueueGroup($queue->Model->queue_group_id);
				$group->remove($item);
			}
			$queue->insert($item);
		}

		/**
		 * Access a queue by its short name, or by ID (int)
		 *
		 * @throws ECash_Queues_QueueException
		 * @param mixed $name_short
		 * @return ECash_Queues_BasicQueue
		 */
		public function getQueue($id)
		{
		//	$id = trim(strtolower($id));
			if (array_key_exists($id, $this->queues))
			{
				return $this->queues[$id];
			}
			throw new ECash_Queues_QueueException("Invalid queue name or ID.");
		}

		/**
		 * Checks for the existence of a queue
		 *
		 * @param string $id - name short
		 * @return bool
		 */
		public function hasQueue($id)
		{
			if (array_key_exists($id, $this->queues))
			{
				return true;
			}
			return false;
		}

		/**
		 * Access a queue group by its short name
		 *
		 * @throws ECash_Queues_QueueException
		 * @param mixed $id
		 * @return ECash_Queues_QueueGroup
		 */
		public function getQueueGroup($id)
		{
			if (array_key_exists($id, $this->groups))
			{
				return $this->groups[$id];
			}
			throw new ECash_Queues_QueueException("Invalid queue group name or ID.");
		}

		/**
		 *
		 * @param ECash_Queues_BasicQueueItem $item
		 * @return ECash_Queues_BasicQueue[]
		 */
		public function removeFromAllQueues(ECash_Queues_BasicQueueItem $item)
		{
			$results = array();
			foreach ($this->queues_unique as $name_short => $queue)
			{
				$removed_item = $queue->retrieveAndRemove($item);
				if ($removed_item !== NULL)
					$results[$name_short] = $removed_item;
			}
			return $results;
		}

		/**
		 *
		 * @param ECash_Queues_BasicQueueItem $item
		 * @return ECash_Queues_BasicQueue[]
		 */
		public function findInAllQueues(ECash_Queues_BasicQueueItem $item)
		{
			$results = array();
			foreach ($this->queues_unique as $name_short => $queue)
			{
				$found_item = $queue->retrieveOnly($item);
				if (!empty($found_item->queue_entry_id))
					$results[$name_short] = $found_item;
			}
			return $results;
		}

		/**
		 * Returns an array of queues that match a specific type, e.g., 'CollectionsQueue', or 'BasicQueue'
		 *
		 * @param string $classtype
		 * @return ECash_Queues_BasicQueue $item
		 */
		public function getQueuesByType($classtype)
		{
			if (isset($this->queues_by_type[$classtype]))
			{
				return $this->queues_by_type[$classtype];
			}
			return array();
		}

	}
?>
