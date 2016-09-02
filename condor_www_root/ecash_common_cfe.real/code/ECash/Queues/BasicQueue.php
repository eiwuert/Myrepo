<?php
	/**
	 * Queue logic class.
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class ECash_Queues_BasicQueue extends ECash_Queues_QueueBase implements ECash_Queues_IQueue
	{
		/**
		 * @var ECash_Models_Queue
		 */
		protected $model;

		/**
		 * contains the config for this queue. may be NULL, as its only
		 * fetched if needed.
		 *
		 * @var ECash_Queues_QueueConfig
		 */
		protected $config;
		
		/**
		 * @return ECash_Queues_QueueConfig
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
		 */
		public function setModel(ECash_Models_Queue $model)
		{
			$this->model = $model;
		}

		/**
		 * read-only property, can rows in this queue be escalated.
		 * @return unknown
		 */
		public function getCanEscalate()
		{
			return ($this->model->escalate_queue_id !== NULL);
		}

		/**
		 * constructor. expects a model
		 *
		 * @param ECash_Models_Queue $queue_model
		 * @param DB_IConnection_1 $db database connector for operations
		 */
		public function __construct(ECash_Models_Queue $queue_model, DB_IConnection_1 $db)
		{
			parent::__construct($db);
			$this->model = $queue_model;
		}

		/**
		 */
		public function remove(ECash_Queues_BasicQueueItem $item)
		{
			// share a transaction
			// @todo replace with DB_TransactionManager_1
			$shared = $this->db->getInTransaction();
			if (!$shared) $this->db->beginTransaction();
			
			$this->onItemRemove($item);
			$this->archiveQueueEntries(array('related_id' => $item->RelatedId, 'queue_id' => $this->model->queue_id));
			$this->deleteQueueEntries(array('related_id' => $item->RelatedId, 'queue_id' => $this->model->queue_id));
			
			if (!$shared) $this->db->commit();
		}

		public function retrieveAndRemove(ECash_Queues_BasicQueueItem $item)
		{
			$returnItem = NULL;
			$model = $this->retrieveOnly($item);
			if (!empty($model->queue_entry_id))
			{
				$returnItem = $this->queueItemFactory($model);
				$this->remove($item);
			}

			return $returnItem;
		}
		
		/**
		 * A function to make it so that you can retrieve the record without dequeing it.
		 * Useful for building histories and checking the status.
		 *
		 * @param ECash_Queues_BasicQueueItem $item
		 * @return ECash_Queues_BasicQueueItem
		 */
		public function retrieveOnly(ECash_Queues_BasicQueueItem $item)
		{
			$model = $this->queueEntryFactory();
			$model->loadBy(array('related_id' => $item->RelatedId, 'queue_id' => $this->model->queue_id));
			return $model;
		}

		/**
		 * @param ECash_Queues_BasicQueueItem $item
		 * @param bool $override_previous
		 * @return bool Update had effect or not
		 */
		public function changeDateAvailable(ECash_Queues_BasicQueueItem $item, $override_later = FALSE)
		{
			$table = $this->getQueueEntryTableName();

			$args = array(
				'date_available' => date("Y-m-d H:i:s", $item->DateAvailable),
				'related_id' => $item->RelatedId,
				'queue_id' => $this->model->queue_id
			);

			$query = "
				update $table
				set
					date_available = :date_available
				where
					related_id = :related_id
					and queue_id = :queue_id
			";

			if ($override_later === FALSE)
			{
				$query .= "
					and date_available < :date_available
				";
			}

			$st = $this->db->queryPrepared($query, $args);

			return ($st->rowCount() > 0);
		}
		
		/**
		 * insert an application into this queue.
		 *
		 * @param ECash_Queues_BasicQueueItem $application_id
		 * @param string $date_available
		 */
		public function insert(ECash_Queues_BasicQueueItem $item)
		{
			$queue_entry = $item->getModel();
			$queue_entry->date_queued = time();
			$queue_entry->queue_id = $this->model->queue_id;
			$this->onItemInsert($queue_entry);
			$queue_entry->save();
			$this->onItemInsertComplete($queue_entry);
		}

		/**
		 * Makes all queue entry's available that were waiting before becoming available.
		 *
		 * @return int The number of queue items made available by this action.
		 */
		public function flushUnavailableItems()
		{
			$st = $this->db->queryPrepared("
                        update " . $this->getQueueEntryTableName() . "
                                set
					date_available = CURRENT_TIMESTAMP
			where
					queue_id = ?
			and date_available > CURRENT_TIMESTAMP", array($this->model->queue_id));

			return $st->rowCount();
		}

		/**
		 * dequeues an application from the queue, and returns a model
		 * for the corresponding queue entry.  Keep in mind that if this is
		 * the final dequeuing of an entry, the returned model will represent a
		 * row that is no longer in the table.  Do not attempt to save it.
		 *
		 * If the queue is empty, NULL will be returned.
		 *
		 * @return ECash_Queues_BasicQueueItem
		 */
		public function dequeue($related_id = NULL, $max_attempts = 3, $retry_wait = 100000)
		{
			if($max_attempts > 0)
			{                        
				// share a transaction
				// @todo replace with DB_TransactionManager_1
				$shared = $this->db->getInTransaction();
				if (!$shared) $this->db->beginTransaction();
			
				$item = NULL;
				$model = $this->queueEntryFactory();
                        
				if(empty($related_id))
				{
					try 
					{
						if(($row = $this->getNextRow($max_attempts)) !== FALSE)
						{
							$model->fromDbRow($row);
							$this->onItemDequeue($model);
							$item = $this->queueItemFactory($model);
                                        
						}       
					}
					//this almost certainly arises from a deadlock
					catch(Exception $e)
					{
						--$max_attempts;
						if($max_attempts > 0)
						{
							if ($this->db->getInTransaction()) $this->db->rollback();
							usleep($retry_wait);
							return $this->dequeue($related_id, $max_attempts);
						}
						else 
						{
							throw $e;
						}
					}
				}
				else
				{
					if($model->loadBy(array('related_id' => $related_id)) !== FALSE)
					{
						$this->onItemDequeue($model);
						$item = $this->queueItemFactory($model);
					}
				}
                        
				if (!$shared) $this->db->commit();
			}
                        
			return $item;
		}

		/**
		 * overridable method that gets called after an item has pulled and is about
		 * to be converted to a QueueItem and returned to the calling code.
		 *
		 * IMPORTANT: Called from within a transaction.
		 *
		 * @param ECash_Models_WritableModel $model
		 */
		protected function onItemDequeue(ECash_Models_WritableModel $model)
		{
			$config = $this->getConfig();

			$recycle_time = $config->getValue('recycle_time');
			$dequeue_limit = $config->getValue('recycle_limit');
			//check if recycle time is set to never
			if($recycle_time == 0)	//set date avaiable to 10 years in future
				$recycle_time = 314496000;

			$this->archiveQueueEntries(array('queue_entry_id' => $model->queue_entry_id));
			
			$model->date_available = time() + $recycle_time;
			$model->dequeue_count++;
			$model->save();
			
			if ($model->dequeue_count >= $dequeue_limit && $dequeue_limit != 0)
			{
				$this->deleteQueueEntries(array('queue_entry_id' => $model->queue_entry_id));
			}
		}
		
		/**
		 * @todo: Make agent affiliation not retarded. Move it into a class and communicate
		 * via observer model or other callback mechanism.  This is not a functionality of the queues
		 * and WILL be separated from it. Until then, however, this must remain for reasons of time.
		 *
		 * @param ECash_Models_WritableModel The row that has just been dequeued
		 */
		protected function createAgentAffiliation(ECash_Models_WritableModel $model)
		{
			$date_expiration = (time() + $this->getConfig()->getValue('recycle_time'));
			$app = ECash::getApplicationById($model->related_id);
			$affiliations = $app->getAffiliations();
			$affiliations->add(ECash::getAgent(), 'queue', 'owner', $date_expiration);
		}

		/**
		 * overloaded method called when a queue item is removed
		 * At the time of removal, it is not typical to have a full model present.
		 * Thus, we receive only the related_id
		 * @param int $related_id
		 */
		protected function onItemRemove(ECash_Queues_BasicQueueItem $item)
		{
			/**
			 * The basic queue doesn't do anything with this. This is purely for future extension in child
			 * classes.
			 */
		}

		/**
		 * overridable method called when a queue item is about to be inserted
		 * Following this call, the model will be saved.
		 * @param ECash_Models_WritableModel $model
		 */
		protected function onItemInsert(ECash_Models_WritableModel $model)
		{
		}

		/**
		 * overridable method called when a queue item is inserted and saving has taken place.
		 * @param ECash_Models_WritableModel $model
		 */
		protected function onItemInsertComplete(ECash_Models_WritableModel $model)
		{
		}

		protected function onItemExpired()
		{
		}

		/**
		 * overridable method to fetch and return the next row from the database (in row form)
		 *
		 * @return ECash_Models_WritableModel
		 */
		protected function getNextRow()
		{
			$query = "
				SELECT *
				FROM " . $this->getQueueEntryTableName() . "
				WHERE
					queue_id = ?
					AND date_available <= ?
					AND (date_expire IS NULL OR date_expire >= ?)
				".$this->getSortOrder()."
				LIMIT 1
                                FOR UPDATE
                        ";

                        $st = $this->db->queryPrepared($query, array(
                                $this->model->queue_id,
                                date("Y-m-d H:i:s"),
				date("Y-m-d H:i:s")
			));

			return $st->fetch();
		}

		/**
		 * Overridable method to create a QueueItem from a queue entry model
		 * @param ECash_Models_WritableModel $model
		 * @return ECash_Queues_BasicQueueItem
		 */
		protected function queueItemFactory(ECash_Models_WritableModel $model)
		{
			return ECash_Queues_BasicQueueItem::fromModel($model);
		}

		/**
		 * overridable method to create a model of the right type for a queue entry in this queue.
		 * @return ECash_Models_QueueEntry
		 */
		protected function queueEntryFactory()
		{
			return new ECash_Models_QueueEntry($this->db);
		}
		
		/**
		 * overridable method to return the queue entry table for this queue.
		 * @return string
		 */
		public function getQueueEntryTableName()
		{
			return 'n_queue_entry';
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
				from " . $this->getQueueEntryTableName() . "
				where
					queue_id = ?
                                and date_available <= ?
                                and (date_expire IS NULL OR date_expire >= ?)
                                ";
			return DB_Util_1::querySingleValue($this->db,
                                        $query,
                                        array(
                                                $this->model->queue_id,
						date("Y-m-d H:i:s"),
						date("Y-m-d H:i:s")
					)
				);
		}

		/**
		 * Checks to see if an item is in the queue
		 */
		public function contains(ECash_Queues_BasicQueueItem $item)
		{
			$query = "
				select count(*)
				from " . $this->getQueueEntryTableName() . "
				where
					queue_id = ?
					and related_id = ?
					and date_available <= ?
                                        and (date_expire IS NULL OR date_expire >= ?)
                        ";

			$count = DB_Util_1::querySingleValue($this->db,
				$query,
				array(
					$this->model->queue_id,
					$item->RelatedId,
					date("Y-m-d H:i:s"),
					date("Y-m-d H:i:s")
					)
				);

			return ($count > 0);
		}

		/**
		 * Checks to see if an item has a queue entry for this queue.
		 * This function has not been tested
		 * 
		 * @param ECash_Queues_BasicQueueItem
		 * @return bool
		 */
		public function entryExists(ECash_Queues_BasicQueueItem $item)
		{
			$query = "
                                select count(*)
                                from " . $this->getQueueEntryTableName() . "
                                where
                                        queue_id = ?
                                        and related_id = ?
                        ";

			$count = DB_Util_1::querySingleValue($this->db,
				$query,
				array(
					$this->model->queue_id,
					$item->RelatedId
					)
				);

			return ($count > 0);
		}

		/**
		 * @param ECash_Queues_BasicQueueItem $item
		 */
		public function escalate(ECash_Queues_BasicQueueItem $item)
		{
			if (!$this->getCanEscalate())
			{
				throw new ECash_Queues_QueueException("This queue does not have the capability of escalation.");
			}

			$queue_manager = ECash::getQueueManager();
			$escalated_queue = $queue_manager->getQueue($this->model->escalate_queue_id);
			$escalated_item = $escalated_queue->getNewQueueItem($item->RelatedId, $this->model->queue_id);
			$escalated_queue->insert($escalated_item);
		}

		/**
		 * Removes any expired items from the queue.
		 *
		 * @return int Returns the number of items flushed.
		 */
		public function flush()
		{
			$query = "
				SELECT *
				FROM " . $this->getQueueEntryTableName() . "
				WHERE
					queue_id = ?
					AND date_expire IS NOT NULL
					AND date_expire <= ?
				FOR UPDATE
			";

			$shared = $this->db->getInTransaction();
			if (!$shared) $this->db->beginTransaction();

			$st = $this->db->queryPrepared($query, array($this->model->queue_id, date("Y-m-d H:i:s")));
			$items_flushed = 0;

			while (($row = $st->fetch(PDO::FETCH_OBJ)) !== FALSE)
			{
				$this->archiveQueueEntries(array('queue_entry_id' => $row->queue_entry_id), self::REMOVE_EXPIRED);
				$this->deleteQueueEntries(array('queue_entry_id' => $row->queue_entry_id));
				$items_flushed++;
			}

			if (!$shared) $this->db->commit();

			return $items_flushed;
		}

		/**
		 * Create a new queue item
		 *
		 * @param int $related_id
		 * @return ECash_Queues_BasicQueueItem
		 */
		public function getNewQueueItem($related_id)
		{
			return new ECash_Queues_BasicQueueItem($related_id);
		}

		/**
		 * Returns the sort order for this queue.
		 * format: order by [...]
		 *
		 * @return string
		 */
		public function getSortOrder()
		{
			return "order by priority desc, date_available asc, date_queued asc";
		}
	}
?>
