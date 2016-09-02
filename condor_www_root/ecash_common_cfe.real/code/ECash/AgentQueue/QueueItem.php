<?php
	
	/**
	 * Queue item type for items which are a part of the agent queues.
	 *
	 * @package Queues
	 * @author  Mike Lively <mike.lively@sellingsource.com>
	 */
	class ECash_AgentQueue_QueueItem extends ECash_Queues_TimeSensitiveQueueItem
	{
		/**
		 * @var int
		 */
		public $AgentId;
		
		/**
		 * @var string
		 */
		public $QueueReason;
		
		/**
		 * @var DB_Models_ReferenceTable_1
		 */
		protected static $reason_table;
		

		/**
		 * Creates a new agent queue item.
		 *
		 * @param int $related_id
		 * @param int $agent_id
		 * @param int $start_hour
		 * @param int $end_hour
		 */
		public function __construct($related_id, $agent_id, $start_hour, $end_hour)
		{
			parent::__construct($related_id, $start_hour, $end_hour);
			$this->AgentId = $agent_id;
		}

		/**
		 * Initializes the given model with queue item data
		 *
		 * @param ECash_Models_WritableModel $model
		 * @return null
		 */
		protected function initModel(ECash_Models_WritableModel $model)
		{
			parent::initModel($model);
			$model->owning_agent_id = $this->AgentId;
			
			$model->agent_queue_reason_id = $this->getReasonModel()->agent_queue_reason_id;
		}
		
		/**
		 * The model factory
		 *
		 * @return eCash_Models_AgentQueueEntry
		 */
		protected function modelFactory()
		{
			return ECash::getFactory()->getModel('AgentQueueEntry');
		}
		
		/**
		 * Returns the reason model for this item.
		 * 
		 * If there is no reason then NULL is returned.
		 *
		 * @return ECash_Models_Reference_AgentQueueReason
		 */
		public function getReasonModel()
		{
			return empty($this->QueueReason)
				? NULL
				: $this->getAgentQueueReason($this->QueueReason);
		}
		
		/**
		 * Returns the agent queue reason model for the given reason.
		 *
		 * @param mixed $reason_name can be a short name or an id.
		 * @return ECash_Models_Reference_AgentQueueReason
		 */
		protected static function getAgentQueueReason($reason_name)
		{
			if (empty(self::$reason_table))
			{
				self::$reason_table = ECash::getFactory()->getReferenceList('AgentQueueReason');
			}
			
			if (isset(self::$reason_table[$reason_name]))
			{
				return self::$reason_table[$reason_name];
			}
			else
			{
				if (is_numeric($reason_name))
				{
					throw new Exception("An agent queue reason can not be created using an ID.");
				}
				$reason = ECash::getFactory()->getReferenceModel('AgentQueueReason');
				$reason->date_created = time();
				$reason->name = $reason_name;
				$reason->name_short = $reason_name;
				$reason->sort = 0;
				$reason->save();
				
				return $reason;
			}
		}

		/**
		 * Creates a QueueItem from a model.  This is an internal method and should
		 * only be called by the queues class directly.
		 *
		 * @param ECash_Models_TimeSensitiveQueueEntry $model
		 * @return ECash_Queues_TimeSensitiveQueueItem
		 */
		public static function fromModel(ECash_Models_AgentQueueEntry $model)
		{
			$item = new ECash_AgentQueue_QueueItem(
				$model->related_id,
				$model->agent_id,
				$model->start_hour,
				$model->end_hour
			);
			
			$item->QueueReason = self::getAgentQueueReason($model->agent_queue_reason_id)->name_short;
			
			return $item;
		}
	}
?>
