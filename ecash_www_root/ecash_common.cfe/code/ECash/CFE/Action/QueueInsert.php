<?php

	/**
	 * An action that inserts the current application into the given queue
	 *
	 */
	class ECash_CFE_Action_QueueInsert extends ECash_CFE_Base_QueueAction
	{
		public function getType()
		{
			return 'QueueInsert';
		}

		public function getParameters()
		{
			return array(
				new ECash_CFE_API_VariableDef('queue', ECash_CFE_API_VariableDef::TYPE_STRING, ECash::getFactory()->getDB()),
				new ECash_CFE_API_VariableDef('Delay', ECash_CFE_API_VariableDef::TYPE_NUMBER, ECash::getFactory()->getDB()),
				new ECash_CFE_API_VariableDef('Expiration', ECash_CFE_API_VariableDef::TYPE_NUMBER, ECash::getFactory()->getDB()),
				new ECash_CFE_API_VariableDef('Priority', ECash_CFE_API_VariableDef::TYPE_NUMBER, ECash::getFactory()->getDB())
			);
		}

		/**
		 * Inserts into the queue
		 *
		 * @param CFE_IContext $c
		 */
		public function execute(ECash_CFE_IContext $c)
		{
			// evaluate any expression parameters
			$params = $this->evalParameters($c);
			if(empty($params['Delay']))
			{
				$params['Delay'] = 0;
			}

			try
			{
				$qm = ECash::getFactory()->getQueueManager();
				$qi = $qm->getQueue($params['queue'])->getNewQueueItem($c->getAttribute('application_id'));
				
				//Delay is now being added to the date_available or the current time. [W!-09-30-2008]
				try
				{
					$date_available = $c->getAttribute('date_available');
				}
				catch (Exception $e)
				{
					$date_available = time();	
				}
				$date_available = $params['Delay'] * 60 + $date_available;
			
				$qi->DateAvailable = $date_available;

				//Expiration is now based on the date_available.
				if(!empty($params['Expiration']))
				{
					$qi->DateExpire = $date_available + ($params['Expiration'] * 60);
				}
				
				if(!empty($params['Priority']))
				{
					$qi->Priority = $params['Priority'];
				}

				$qm->moveToQueue($qi, $params['queue']) ;
				
			}
			catch (ECash_Queues_QueueException $e)
			{
				throw new ECash_CFE_RuntimeException('Error while inserting into queue "'.$params['queue'].'": '.$e->getMessage());
			}
		}
	}

?>
