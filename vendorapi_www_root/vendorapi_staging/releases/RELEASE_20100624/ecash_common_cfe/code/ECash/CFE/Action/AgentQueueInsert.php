<?php

	/**
	 * An action that inserts the current application into the current agent's myqueue
	 *
	 */
	class ECash_CFE_Action_AgentQueueInsert extends ECash_CFE_Base_QueueAction
	{
		public function getType()
		{
			return 'AgentQueueInsert';
		}

		public function getReferenceData($param_name) 
		{
			$retval = array();

			switch($param_name) 
			{
				case "Agent_Type":
					$retval[] = array('current', 'Current Agent', 0);
					$retval[] = array('controlling', 'Collections Controlling Agent', 0);
					break;
			}
			return $retval;
		}

		public function getParameters()
		{
			return array(
				new ECash_CFE_API_VariableDef('Delay', ECash_CFE_API_VariableDef::TYPE_NUMBER, ECash::getFactory()->getDB()),
				new ECash_CFE_API_VariableDef('Reason', ECash_CFE_API_VariableDef::TYPE_STRING, ECash::getFactory()->getDB()),
				new ECash_CFE_API_VariableDef('Expiration', ECash_CFE_API_VariableDef::TYPE_NUMBER, ECash::getFactory()->getDB()),
				new ECash_CFE_API_VariableDef('Agent_Type', ECash_CFE_API_VariableDef::TYPE_STRING, ECash::getFactory()->getDB()),
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

			if(empty($params['Agent_Type']))
			{
				$params['Agent_Type'] = 'current';
			}

			try
			{
				
				$application = ECash::getApplicationById($c->getAttribute('application_id'));

				
				

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
				
				
				//Expiration is now based on the date_available.
				if(!empty($params['Expiration']))
				{
					$expiration = $date_available + ($params['Expiration'] * 60);
				}
				
				if(!empty($params['Reason']))
				{
					$reason = $params['Reason'];
				}
				else 
				{
					$reason = 'collections';
				}

				if ($params['Agent_Type'] == 'current')
				{
					$agent = ECash::getAgent();
				}
				else if ($params['Agent_Type'] == 'controlling')
				{
					$m_agent = ECash::getFactory()->getModel('AgentAffiliation');

					if ($m_agent->loadActiveAffiliation($c->getAttribute('application_id'), 'collections', 'owner') == FALSE)
					{
						$agent = ECash::getAgent();
					}
					else
					{
						$agent_id = $m_agent->agent_id;
						$agent    = ECash::getAgentById($agent_id);
					}
				}
				else
				{
					// Default to current agent when nothing is specified
					$agent = ECash::getAgent();
				}

				$agent->getQueue()->insertApplication($application, $reason, $expiration, $date_available);
			}
			catch (ECash_Queues_QueueException $e)
			{
				throw new ECash_CFE_RuntimeException('Error while inserting into Agent queue : '.$e->getMessage());
			}
		}
	}

?>
