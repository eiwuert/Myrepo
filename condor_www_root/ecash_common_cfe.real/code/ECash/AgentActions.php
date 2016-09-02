<?php

	class ECash_AgentActions extends Object_1
	{
		protected function getModel($action_name)
		{
			$action = ECash::getFactory()->getModel('Action');
			if ($action->loadBy(array('name_short' => $action_name)))
			{
				return $action;
			}
			else return $this->createModel($action_name);
		}

		protected function createModel($action_name)
		{
			$new_action = ECash::getFactory()->getModel('Action');

			$new_action->date_created = time();
			$new_action->name = $action_name;
			$new_action->name_short = $action_name;
			$new_action->save();

			return $new_action;
		}

		public function addAgentAction($company_id, $agent_id, $action_name, $application_id = NULL, $time_expended = NULL)
		{
			$action = $this->getModel($action_name);

		    $agent_action = ECash::getFactory()->getModel('AgentAction');

		    $agent_action->company_id = $company_id;
		    $agent_action->agent_id = $agent_id;
		    $agent_action->action_id = $action->action_id;
		    $agent_action->application_id = $application_id;
		    $agent_action->time_expended = $time_expended;
		    $agent_action->date_created = time();
			$agent_action->save();
		}
	}
