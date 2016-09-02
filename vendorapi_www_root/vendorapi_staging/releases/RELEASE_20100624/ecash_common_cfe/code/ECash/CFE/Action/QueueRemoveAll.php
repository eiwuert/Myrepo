<?php

	/**
	 * Remove from all queues
	 *
	 */
	class ECash_CFE_Action_QueueRemoveAll extends ECash_CFE_Base_BaseAction
	{
		public function getType()
		{
			return 'QueueRemoveAll';
		}

		public function getParameters()
		{
			return array();
		}

		public function execute(ECash_CFE_IContext $c)
		{
			$qi = new ECash_Queues_BasicQueueItem($c->getAttribute('application_id'));

			try
			{
				/**
				 * @var ECash_Queues_QueueManager $qm
				 */
				$qm = ECash::getFactory()->getQueueManager();
				$qm->removeFromAllQueues($qi);
			}
			catch (ECash_Queues_QueueException $e)
			{
				throw new ECash_CFE_RuntimeException($e);
			}
		}
	}

?>
