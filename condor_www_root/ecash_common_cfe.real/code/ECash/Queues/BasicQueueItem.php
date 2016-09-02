<?php

	/**
	 * The most basic queue item type.
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class ECash_Queues_BasicQueueItem extends Object_1
	{
		/**
		 * The unique (Within your code) piece of data that you can relate back to
		 * your record. Whether this is an email, application or otherwise, it should
		 * only matter to you.
		 * @var int
		 */
		public $RelatedId;

		/**
		 * To prioritize this item.  If untouched, will use the default
		 * sorting method for this queue.  When priority is equal, default sorting
		 * is used.
		 * @var int
		 */
		public $Priority;
		
		/**
		 * unix timestamp for when this queue item should be removed from the queue
		 * @var int
		 */
		public $DateExpire;

		/**
		 * unix timestamp for when this queue item should be 'available' in the queue
		 * @var int
		 */
		public $DateAvailable;

		/**
		 * Associated agent id
		 * @var int
		 */
		public $AgentId;

		/**
		 * @param int $related_id
		 */
		public function __construct($related_id)
		{
			$this->RelatedId = $related_id;
			$this->DateAvailable = time();
			$this->DateExpire = NULL;
			$this->Priority = 100;
			$this->AgentId = NULL;
		}

		/**
		 * returns the model for this queue item.
		 * this is used internally by the queue libraries, and
		 * should serve no real use to someone writing code.
		 *
		 * If you're using this method, you're probably doing something
		 * wrong.
		 *
		 * @internal
		 * @return ECash_Models_WritableModel
		 */
		public function getModel()
		{
			$model = $this->modelFactory();
			$this->initModel($model);
			return $model;
		}

		/**
		 * Sets up the model
		 * @param ECash_Models_WritableModel $model
		 */
		protected function initModel(ECash_Models_WritableModel $model)
		{
			$model->related_id = $this->RelatedId;
			$model->priority = $this->Priority;
			$model->date_available = $this->DateAvailable;
			$model->date_expire = $this->DateExpire;

			if ($this->AgentId !== NULL)
			{
				$model->agent_id = $this->AgentId;
			}
			else
			{
				/**
				 * @todo: Remove this hack.
				 */
				$model->agent_id = ECash::getAgent()->getAgentId();
			}
		}

		/**
		 * Factory to create the models .. this should be overriden
		 * in child classes
		 * @return ECash_Models_WritableModel
		 */
		protected function modelFactory()
		{
			return new ECash_Models_QueueEntry(ECash_Config::getMasterDbConnection());
		}

		/**
		 * Creates a QueueItem from a model.  This is an internal method and should
		 * only be called by the queues class directly.
		 *
		 * @param ECash_Models_QueueEntry $model
		 * @return ECash_Queues_BasicQueueItem
		 */
		public static function fromModel(ECash_Models_QueueEntry $model)
		{
			return new ECash_Queues_BasicQueueItem(
				$model->related_id
			);
		}
	}
?>
