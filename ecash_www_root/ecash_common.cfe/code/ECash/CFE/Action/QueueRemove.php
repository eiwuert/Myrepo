<?php

	class ECash_CFE_Action_QueueRemove extends ECash_CFE_Base_QueueAction
	{
		public function getType()
		{
			return 'QueueRemove';
		}

		public function execute(ECash_CFE_IContext $c)
		{
			// evaluate any expression parameters
			$params = $this->evalParameters($c);
			
			$qi = new ECash_Queues_BasicQueueItem($c->getAttribute('application_id'));
			
			try
			{
				$qm = ECash::getFactory()->getQueueManager();
				$qm->getQueue($params['queue'])->remove($qi);
			}
			catch (ECash_Queues_QueueException $e)
			{
				throw new ECash_CFE_RuntimeException($e);
			}
		}
	}

?>
