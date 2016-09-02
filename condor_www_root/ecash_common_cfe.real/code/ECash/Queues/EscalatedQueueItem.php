<?php
	/**
	 * Queue item type for items which are only active during certain periods of the day
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class ECash_Queues_EscalatedQueueItem extends ECash_Queues_BasicQueueItem
	{
		/**
		 * @var int
		 */
		public $SourceQueueId;

		/**
		 * @param int $related_id
		 * @param int $source_queue_id
		 * @param int $source_queue_entry_id
		 */
		public function __construct($related_id, $source_queue_id)
		{
			parent::__construct($related_id);
			$this->SourceQueueId = $source_queue_id;
		}

		/**
		 * set me up the model
		 * @param ECash_Models_WritableModel $model
		 */
		protected function initModel(ECash_Models_WritableModel $model)
		{
			parent::initModel($model);
			$model->source_queue_id = $this->SourceQueueId;
		}

		/**
		 * model factory
		 * @return ECash_Models_EscalatedQueueEntry
		 */
		protected function modelFactory()
		{
			return new ECash_Models_EscalatedQueueEntry(ECash_Config::getMasterDbConnection());
		}

		/**
		 * Creates a QueueItem from a model.  This is an internal method and should
		 * only be called by the queues class directly.
		 *
		 * @param ECash_Models_EscalatedQueueEntry $model
		 * @return ECash_Queues_EscalatedQueueItem
		 */
		public static function fromModel(ECash_Models_EscalatedQueueEntry $model)
		{
			return new ECash_Queues_EscalatedQueueItem(
				$model->related_id,
				$model->source_queue_id
			);
		}
	}
?>