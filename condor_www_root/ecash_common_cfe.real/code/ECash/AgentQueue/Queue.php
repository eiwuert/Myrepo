<?php
/**
 * The agent queue.
 *
 * This queue is used to provide agents with specific applications to work.
 *
 * @package Queues
 * @author  Mike Lively <mike.lively@sellingsource.com>
 */
class ECash_AgentQueue_Queue extends ECash_Queues_TimeSensitiveQueue
{
	/**
	 * @var int
	 */
	protected $agent_id;
	
	/**
	 * Overridable method called when a queue item expires before being pulled.
	 *
	 * @param ECash_Models_WritableModel $model
	 * @return null
	 */
	protected function onItemExpired(ECash_Models_WritableModel $model)
	{
		$handler = $this->getHandler($model);
		
		if ($handler)
		{
			$application = ECash::getFactory()->getApplication($model->related_id, ECash::getCompany()->company_id);
			$handler->expire($application);
		}
	}
	
	/**
	 * overridable method that gets called after an item has pulled and is about
	 * to be converted to a QueueItem and returned to the calling code.
	 *
	 * IMPORTANT: Called from within a transaction.
	 *
	 * @param ECash_Models_WritableModel $model
	 * @return null
	 */
	protected function onItemDequeue(ECash_Models_WritableModel $model)
	{
		parent::onItemDequeue($model);
		$handler = $this->getHandler($model);
		
		if ($handler)
		{
			$application = ECash::getFactory()->getApplication($model->related_id, ECash::getCompany()->company_id);
			$handler->pull($application);
		}
	}
	
	/**
	 * Returns an instance of the appropriate handler for the given queue entry.
	 *
	 * @param ECash_Models_AgentQueueEntry $model
	 * @return ECash_AgentQueue_Handler_Base
	 */
	protected function getHandler(ECash_Models_AgentQueueEntry $entry)
	{
		$item = $this->queueItemFactory($entry);
		
		$handler_class = $item->getReasonModel()->handler_class;
		
		if(!empty($handler_class))
		{
			if (!class_exists($handler_class, true))
			{
				throw new Exception("Could not find handler for the {$item->getReasonModel()->name_short} reason: {$handler_class}");
			}
			return new $handler_class;
		}
		
		return false;
	}
	
	/**
	 * Creates a new queue entry model
	 *
	 * @return ECash_Models_AgentQueueEntry
	 */
	protected function queueEntryFactory()
	{
		return ECash::getFactory()->getModel('AgentQueueEntry');
	}

	/**
	 * Creates a new agent queue item using the given model
	 *
	 * @param ECash_Models_WritableModel $model
	 * @return ECash_AgentQueue_QueueItem
	 */
	protected function queueItemFactory(ECash_Models_WritableModel $model)
	{
		return ECash_AgentQueue_QueueItem::fromModel($model);
	}

	/**
	 * The name of the table containing agent queue entries.
	 *
	 * @return string
	 */
	public function getQueueEntryTableName()
	{
		return 'n_agent_queue_entry';
	}
	
	/**
	 * The name of the table containing agent queue reasons.
	 *
	 * @return string
	 */
	public function getQueueReasonTableName()
	{
		return 'n_agent_queue_reason';
	}
	
	/**
	 * Sets the agent that for the agent queue.
	 *
	 * @param int $agent_id
	 * @return null
	 */
	public function setAgentId($agent_id)
	{
		$this->agent_id = $agent_id;
	}
	
	/**
	 * Inserts a new application into the queue.
	 *
	 * @param ECash_Application $application The application to insert.
	 * @param string $date_expire The MySQL timestamp for when the entry will expire.
	 * @param string $date_available An optional MySQL time stamp indicating when the item will be available.
	 * @return null
	 */
	public function insertApplication(ECash_Application $application, $queue_reason, $date_expire, $date_available = NULL)
	{
		if (empty($this->agent_id))
		{
			throw new Exception("Applications can not be inserted into an agent queue without an agent being set.");
		}

		$queue_item = $this->getNewQueueItem($application->application_id);
		$queue_item->DateExpire = $date_expire;
		$queue_item->QueueReason = $queue_reason;
		if(!empty($date_available))
		{
			$queue_item->DateAvailable = $date_available;		
		}

		if(!$this->entryExists($queue_item))
		{
			$this->insert($queue_item);
		}
		else
		{
			/*
			 * If this app is already in the queue then update
			 * the entry
			 */
			$agent_entry = $this->queueEntryFactory();
			if ($agent_entry->loadBy(
					array('related_id' => $application->application_id, 'queue_id' => $this->model->queue_id)
				)) 
			{
				$agent_entry->date_available = $date_available;
				$agent_entry->agent_id = $this->agent_id;
				$agent_entry->owning_agent_id = $this->agent_id;
				$agent_entry->date_expire = $date_expire;
				$agent_entry->save();
			}
			else
			{
				throw new Exception("Could not insert agent queue item and the item does not already exist. agent_id: {$this->agent_id} | application_id: {$application->ApplicationId}");
			}
		}
	}
	
	/**
	 * Returns the next queue entry
	 *
	 * @return ECash_Models_WritableModel
	 */
	protected function getNextRow()
	{
		$time = localtime(time(), TRUE);

		$query = "
			select
				*
			from {$this->getQueueEntryTableName()} qe
				JOIN {$this->getQueueReasonTableName()} qr USING (agent_queue_reason_id)
			where
				qe.queue_id = ?
				and qe.date_available <= ?
				and qe.start_hour <= ?
				and qe.end_hour > ?
				and (qe.date_expire IS NULL OR qe.date_expire >= ?)
				and qe.owning_agent_id = ?
			" . $this->getSortOrder() . "
			limit 1
		";
		
		$st = DB_Util_1::queryPrepared(
			ECash_Config::getMasterDbConnection(),
			$query,
			array(
				$this->model->queue_id,
				date("Y-m-d H:i:s"),
				$time['tm_hour'],
				$time['tm_hour'],
				date("Y-m-d H:i:s"),
				$this->agent_id
			)
		);

		return $st->fetch();
	}
	
	/**
	 * Returns the number of items in the queue
	 *
	 * @return int
	 */
	public function count()
	{
		$time = localtime(time(), TRUE);
		
		$query = "
			select count(*)
			from " . $this->getQueueEntryTableName() . "
			where
				queue_id = ?
				and date_available <= ?
				and start_hour <= ?
				and end_hour > ?
				and (date_expire IS NULL OR date_expire >= ?)
				and owning_agent_id = ?
		";
		
		return DB_Util_1::querySingleValue(
			ECash_Config::getMasterDbConnection(),
			$query,
			array(
				$this->model->queue_id,
				date("Y-m-d H:i:s"),
				$time['tm_hour'],
				$time['tm_hour'],
				date("Y-m-d H:i:s"),
				$this->agent_id
			)
		);
	}		

	/**
	 * Creates a new queue item.
	 *
	 * @param int $related_id The application_id
	 * @return ECash_AgentQueue_QueueItem
	 */
	public function getNewQueueItem($related_id)
	{
		$offset = $this->getApplicationTimeZoneOffset($related_id);
		
		$item = new ECash_AgentQueue_QueueItem(
			$related_id,
			$this->agent_id,
			ECash_Config::getInstance()->LOCAL_EARLIEST_CALL_TIME + $offset,
			ECash_Config::getInstance()->LOCAL_LATEST_CALL_TIME + $offset
		);
		return $item;
	}
	
	/**
	 * Reassigns the agent's queue items to a new agent.
	 *
	 * Returns the number of queue items moved.
	 * 
	 * @param ECash_Agent $to_agent
	 * @return int
	 */
	public function reassign(ECash_Agent $to_agent)
	{
		$queue = $this->queueEntryFactory();
		return $queue->reassign($this->agent_id, $to_agent->AgentId);
	}
	
	/* Checks to see if an item has a queue entry for this queue.
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
	 * Returns the sort order for this queue.
	 * format: order by [...]
	 *
	 * @return string
	 */
	public function getSortOrder()
	{
		return "order by priority desc, date_queued asc";
	}
}

?>
