<?php

/**
 * Event handlers for agent queue operations.
 *
 * @package Agent.Queue
 * @author  Mike Lively <mike.lively@sellingsource.com>
 */
class ECash_AgentQueue_Handler_Base
{
	/**
	 * Called on applications that expire in the queue.
	 *
	 * @param ECash_Application $application
	 * @return null
	 */
	public function expire(ECash_Application $application)
	{
	}
	
	/**
	 * Called on applications that are pulled from the queue.
	 *
	 * @param ECash_Application $ecash
	 * @return null
	 */
	public function pull(ECash_Application $application)
	{
		$this->hitAgentAction("search_myapps_", $application);
	}
	
	/**
	 * Hits the agent action for this application.
	 *
	 * @param unknown_type $search_action
	 * @param ECash_Application $application
	 * @param ECash_Agent $agent
	 * @return null
	 * @todo Get rid of code duplication...
	 */
	protected function hitAgentAction($search_action, ECash_Application $application)
	{
		$actions = new ECash_AgentActions();
		$actions->addAgentAction(
			$application->getCompanyId(),
			ECash::getAgent()->getAgentId(), 
			$search_action,
			$application->getId()
		);
	}
}
?>