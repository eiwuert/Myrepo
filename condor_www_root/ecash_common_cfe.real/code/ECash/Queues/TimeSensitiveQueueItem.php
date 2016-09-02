<?php
	/**
	 * Queue item type for items which are only active during certain periods of the day
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class ECash_Queues_TimeSensitiveQueueItem extends ECash_Queues_BasicQueueItem
	{
		/**
		 * Hour this item becomes available (e.g., 15 = 3pm)
		 * local time.
		 *
		 * @var int
		 */
		public $StartHour;

		/**
		 * Hour this item is no longer available (e.g., 16 = 4pm)
		 * local time.
		 *
		 * @var int
		 */
		public $EndHour;

		/**
		 * @param int $related_id
		 * @param int $start_hour
		 * @param int $end_hour
		 */
		public function __construct($related_id, $start_hour, $end_hour)
		{
			parent::__construct($related_id);
			$this->StartHour = $start_hour;
			$this->EndHour = $end_hour;
		}

		/**
		 * set me up the model
		 * @param ECash_Models_WritableModel $model
		 */
		protected function initModel(ECash_Models_WritableModel $model)
		{
			parent::initModel($model);
			$model->start_hour = $this->StartHour;
			$model->end_hour = $this->EndHour;
		}
		/**
		 * model factory
		 * @return ECash_Models_TimeSensitiveQueueEntry
		 */
		protected function modelFactory()
		{
			return new ECash_Models_TimeSensitiveQueueEntry(ECash_Config::getMasterDbConnection());
		}

		/**
		 * Creates a QueueItem from a model.  This is an internal method and should
		 * only be called by the queues class directly.
		 *
		 * @param ECash_Models_TimeSensitiveQueueEntry $model
		 * @return ECash_Queues_TimeSensitiveQueueItem
		 */
		public static function fromModel(ECash_Models_TimeSensitiveQueueEntry $model)
		{
			return new ECash_Queues_TimeSensitiveQueueItem(
				$model->related_id,
				$model->start_hour,
				$model->end_hour
			);
		}
	}
?>