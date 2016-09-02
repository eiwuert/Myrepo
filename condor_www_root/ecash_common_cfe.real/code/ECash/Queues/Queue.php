<?php
	/**
	 * Queue logic class.
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class ECash_Queues_Queue extends ECash_Queues_QueueBase implements ECash_Queues_IQueue
	{
		/**
		 * @var ECash_Models_Queue
		 */
		protected $model;

		/**
		 * contains the config for this queue. may be NULL, as its only
		 * fetched if needed.
		 *
		 * @var ECash_QueueConfig
		 */
		protected $config;

		/**
		 * @return ECash_QueueConfig
		 */
		public function getConfig()
		{
			if ($this->config === NULL)
			{
				$this->config = new ECash_Queues_QueueConfig($this);
			}
			return $this->config;
		}

		/**
		 * @return ECash_Models_Queue
		 */
		public function getModel()
		{
			return $this->model;
		}

		/**
		 * @param ECash_Models_Queue $model
		 * @param DB_IConnection_1 $db database connector for operations
		 */
		public function setModel(ECash_Models_Queue $model, DB_IConnection_1 $db)
		{
			parent::__construct($db);
			$this->model = $model;
		}

		/**
		 * constructor. expects a model
		 *
		 * @param ECash_Models_Queue $queue_model
		 */
		public function __construct(ECash_Models_Queue $queue_model)
		{
			$this->model = $queue_model;
		}

		/**
		 * Delete an application from this queue.
		 *
		 * @param int $application_id
		 */
		public function remove($application_id)
		{
			$this->acquireLock();
			$this->archiveQueueEntries($application_id, $this->model->queue_id);
			$this->deleteQueueEntries($application_id, $this->model->queue_id);
			$this->releaseLock();
		}

		/**
		 * insert an application into this queue.
		 *
		 * @param int $application_id
		 * @param string $date_available
		 */
		public function insert($application_id, $date_available = NULL)
		{
			$queue_entry = new ECash_Models_QueueEntry();

			$queue_entry->queue_entry_id = NULL;
			$queue_entry->date_queued = time();
			$queue_entry->date_available = ($date_available ? strtotime($date_available) : time());
			$queue_entry->queue_id = $this->model->queue_id;
			$queue_entry->application_id = $application_id;
			$queue_entry->priority = 100;

			$queue_entry->save();
		}

		/**
		 * dequeues an application from the queue, and returns a model
		 * for the corresponding queue entry.  Keep in mind that if this is
		 * the final dequeuing of an entry, the returned model will represent a
		 * row that is no longer in the table.  Do not attempt to save it.
		 *
		 * If the queue is empty, NULL will be returned.
		 *
		 * @todo This does not currently use business rules for recycling rules.
		 *
		 * @return ECash_Models_QueueEntry
		 */
		public function dequeue()
		{
			$query = "
				update n_queue_entry
				set
					locked = CONNECTION_ID()
				where
					queue_id = ?
					and date_available <= ?
					and locked is null
				order by " . $this->model->sort_order . "
				limit 1";

			$st = $this->db->prepare($query);
			$st->execute(array(
				$this->model->queue_id,
				date("Y-m-d H:i:s")
			));

			$query = "
				select *
				from n_queue_entry
				where
					locked = CONNECTION_ID()
			";

			$st = $this->db->query($query);
			$entry = NULL;

			if (($row = $st->fetch()) !== FALSE)
			{
				$config = $this->getConfig();

				$recycle_time = $config->getValue('recycle_time');
				$dequeue_limit = $config->getValue('recycle_limit');

				$entry = new ECash_Models_QueueEntry();
				$entry->fromDbRow($row);
				$entry->date_available = time() + $recycle_time;
				$entry->dequeue_count++;
				$entry->save();

				if ($entry->dequeue_count == $dequeue_limit)
				{
					$this->archiveLockedEntries($entry->application_id);
					$this->deleteLockedEntries($entry->application_id);
				}
			}

			$this->releaseLocks();

			return $entry;
		}

		/**
		 * returns a count of the number of items in the queue
		 *
		 * @return int
		 */
		public function count()
		{
			$query = "
				select count(*)
				from n_queue_entry
				where
					queue_id = ?
					and date_available <= ?
			";

			$st = $this->db->prepare($query);
			$st->execute(array(
				$this->model->queue_id,
				date("Y-m-d H:i:s")
			));

			return $st->fetch(PDO::FETCH_COLUMN, 0);
		}

		/**
		 * Checks to see if a given application is in the queue
		 *
		 * @param int $application_id
		 * @return bool
		 */
		public function contains($application_id)
		{
			$query = "
				select count(*)
				from n_queue_entry
				where
					queue_id = ?
					and application_id = ?
					and date_available <= ?
			";

			$count = DB_Util_1::querySingleValue($this->db, $query, array($this->model->queue_id, $application_id, date("Y-m-d H:i:s")));

			return ($count > 0);
		}
	}
?>