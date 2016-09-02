<?php
	/**
	 * Queue Group logic class.  Used for managing multiple queues.
	 * e.g., 'the automated queues'
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class ECash_Queues_QueueGroup extends ECash_Queues_QueueBase
	{
		/**
		 * @var ECash_Models_QueueGroup
		 */
		protected $model;

		/**
		 * @var array
		 */
		protected $queues;

		/**
		 * @var array
		 */
		protected $queue_tables;

		/**
		 * this class operates on the model provided
		 *
		 * @param ECash_Models_QueueGroup $model
		 */
		public function __construct(ECash_Models_QueueGroup $model, DB_IConnection_1 $db)
		{
			parent::__construct($db);
			$this->model = $model;
			$this->queues = array();
		}

		/**
		 * @return ECash_Models_QueueGroup
		 */
		public function getModel()
		{
			return $this->model;
		}

		/**
		 * returns all queues that are members of this group.
		 *
		 * @return array
		 */
		public function getQueues()
		{
			return $this->queues;
		}

		/**
		 * argument can be queue.name_short, or queue.queue_id
		 *
		 * @param mixed $queue_id
		 * @return ECash_Queues_BasicQueue
		 */
		public function getQueue($queue_id)
		{
			if (array_key_exists($queue_id, $this->queues))
			{
				return $this->queues[$queue_id];
			}
			throw new ECash_Queues_QueueException("Queue (id=$queue_id) does not exist or is not a member of this group.");
		}

		/**
		 * Adds the queue object for a sub-queue of this group
		 * This should really be 'internal', but php doesn't support that.
		 *
		 * @param ECash_Queues_BasicQueue $queue
		 */
		public function addQueueRef(ECash_Queues_BasicQueue $queue)
		{
			$table = $queue->getQueueEntryTableName();
			$this->queues[$queue->Model->queue_id] = $queue;
			$this->queues[$queue->Model->name_short] = $queue;
			$this->queue_tables[$table] = $table;
		}

		/**
		 * remove an application from all queues in this group.
		 *
		 */
		public function remove(ECash_Queues_BasicQueueItem $item)
		{
			// share a transaction
			// @todo replace with DB_TransactionManager_1
			$shared = $this->db->getInTransaction();
			if (!$shared) $this->db->beginTransaction();

			$this->archiveQueueEntriesByGroup($item->RelatedId);
			$this->deleteQueueEntriesByGroup($item->RelatedId);

			if (!$shared) $this->db->commit();
		}

		/**
		 * Archives history rows for the whole group
		 *
		 * @param int $related_id
		 */
		protected function archiveQueueEntriesByGroup($related_id)
		{
			foreach ($this->queue_tables as $queue_table)
			{
				$query = "
					insert into n_queue_history
					select
						null,
						q.date_available,
						CURRENT_TIMESTAMP(),
						q.queue_entry_id,
						q.queue_id,
						q.related_id,
						q.agent_id,
						:removal_agent_id,
						q.dequeue_count,
						:reason
					from $queue_table q
					join n_queue nq on (nq.queue_id = q.queue_id)
					where
						q.related_id = :related_id
						and nq.queue_group_id = :queue_group_id
						and q.date_available < now()
					for update
				";

				/**
				 * @todo: Remove session dependance.
				 */
				$args = array(
					'reason' => self::REMOVE_GROUP,
					'related_id' => $related_id,
					'removal_agent_id' => ECash::getAgent()->getAgentId(),
					'queue_group_id' => $this->model->queue_group_id
				);
				$this->db->queryPrepared($query, $args);
			}
		}

		/**
		 * Deletes all queue items for a specific queue group
		 *
		 * @param int $related_id
		 */
		protected function deleteQueueEntriesByGroup($related_id)
		{
			foreach ($this->queue_tables as $queue_table)
			{
				$query = "
					delete $queue_table q_entry
					from $queue_table q_entry, n_queue
					where
						related_id = ?
						and q_entry.queue_id = n_queue.queue_id
						and n_queue.queue_group_id = ?
				";

				$args = array($related_id, $this->model->queue_group_id);
				$this->db->queryPrepared($query, $args);
			}
		}
	}
