<?php

	class ECash_Agent_Tracking extends ECash_Agent_Component
	{
		/**
		 * @var ECash_AgentActions
		 */
		protected $agent_actions = NULL;

		/**
		 * @param DB_IConnection_1 $db
		 * @param int $agent_id
		 * @param int $company_id
		 */
		public function __construct(DB_IConnection_1 $db, $agent_id, $company_id)
		{
			parent::__construct($db, $agent_id, $company_id);
			$this->agent_actions = new ECash_AgentActions();
		}

		/**
		 * Add an action. If the action_name does not exist, it will be created.
		 *
		 * @param string $action_name
		 * @param int $application_id
		 * @param int $time_expended
		 */
		public function add($action_name, $application_id = NULL, $time_expended = NULL)
		{
			$this->agent_actions->addAgentAction($this->company_id, $this->agent_id, $action_name, $application_id, $time_expended);
		}
	}
?>
