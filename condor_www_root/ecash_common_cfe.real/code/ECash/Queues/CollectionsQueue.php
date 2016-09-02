<?php

	class ECash_Queues_CollectionsQueue extends ECash_Queues_TimeSensitiveQueue 
	{
		/**
		 * overridden to specify the queue type to the AA library.
		 *
		 * @param ECash_Models_WritableModel $model
		 */
		protected function createAgentAffiliation(ECash_Models_WritableModel $model)
		{
			$date_expiration = (time() + $this->getConfig()->getValue('recycle_time'));
			$app = ECash::getApplicationById($model->related_id);
			$affiliations = $app->getAffiliations();
			$affiliations->add(ECash::getAgent(), 'collections', 'owner', $date_expiration);
		}
		
		/**
		 * Overrides base class
		 */
		public function getSortOrder()
		{
			return "order by priority desc, date_available, date_queued";
		}
		
		/**
		 * Collections queue pulls have agent affiliations to show agent ownership.
		 *
		 * IMPORTANT: Called from within a transaction.
		 *
		 * @param ECash_Models_WritableModel $model
		 */
		protected function onItemDequeue(ECash_Models_WritableModel $model)
		{
			parent::onItemDequeue($model);

			/**
			 * @todo: EXTRACT THIS
			 */
		//	$this->createAgentAffiliation($model);

		}
	}
?>
