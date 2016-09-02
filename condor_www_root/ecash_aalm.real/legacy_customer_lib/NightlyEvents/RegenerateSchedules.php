<?php

	class ECash_NightlyEvent_RegenerateSchedules extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = NULL;
		protected $timer_name = 'Regenerate_Schedules';
		protected $process_log_name = 'regenerate_schedules';
		protected $use_transaction = FALSE;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			
			parent::__construct();
		}
		
		/**
		 * A wrapper for the function Regenerate_Schedules()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
			$this->Regenerate_Schedules();
		}

		/**
		 * Prototype function to regenerate schedules for Active accounts
		 * that have a balance and no scheduled events.
		 * 
		 * Uses: 
		 * - Loan Types with no minimum payments (Agean Request)
		 * - Collections Accounts returning to Active
		 * - Accounts with somehow invalidated schedules
		 *
		 */
		private function Regenerate_Schedules()
		{
			/**
			 * First, find the appropriate rule_sets
			 */
			$rule_component_id = $this->getRuleComponentId('principal_payment');
			$rule_component_parm_id = $this->getRuleComponentParmId($rule_component_id, 'principal_payment_percentage');
			$rule_set_values = $this->getRuleSetsValues($rule_component_id, $rule_component_parm_id, $this->company_id);
			
			if(count($rule_set_values) === 0)
			{
				$this->log->Write("No rule sets that use principal_payment_percentage available for processing.");
				return TRUE;
			}
			
			/**
			 * Contains all the rule ids with 0 as the value
			 */
			$rule_sets = array();
			
			foreach($rule_set_values as $rule_set)
			{
				if($rule_set['parm_value'] === '0')
				{
					$rule_sets[] = $rule_set['rule_set_id'];
				}
			}
			
			if(count($rule_sets) === 0)
			{
				$this->log->Write("No rule sets that use principal_payment_percentage have a value of 0.");
				return TRUE;
			}
			
			$active_status = Status_Utility::Get_Status_ID_By_Chain('active::servicing::customer::*root');
		
			$query = "    -- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
			SELECT  app.application_id,
			        bal.total_balance,
			        bal.principal_balance,
			        bal.service_charge_balance,
			        (
			            SELECT COUNT(es.event_schedule_id)
			            FROM event_schedule AS es
			            WHERE es.application_id = app.application_id
			            AND es.event_status = 'scheduled'
			        ) AS num_scheduled_events
			FROM    application AS app
			JOIN    (
			            SELECT ea.application_id,
			                   SUM(IF(eat.name_short = 'principal', ea.amount, 0))      AS principal_balance,
			                   SUM(IF(eat.name_short = 'service_charge', ea.amount, 0)) AS service_charge_balance,
			                   SUM(IF(eat.name_short = 'fee', ea.amount, 0))            AS fee_balance,
			                   SUM(ea.amount)                                           AS total_balance
			            FROM event_amount AS ea
			            JOIN event_amount_type AS eat ON (ea.event_amount_type_id = eat.event_amount_type_id)
			            JOIN transaction_register AS tr ON (ea.transaction_register_id = tr.transaction_register_id)
			            WHERE tr.transaction_status != 'failed'
			            GROUP BY application_id
			        ) AS bal ON (bal.application_id = app.application_id)
			WHERE   app.company_id = {$this->company_id}
			AND     app.application_status_id = {$active_status}
			AND	    rule_set_id IN (" . implode(',', $rule_sets) . ")
			HAVING  num_scheduled_events = 0	
			AND     total_balance > 0";
			
			$result = $this->db->Query($query);
			$generate_accounts = array();
			
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$generate_accounts[] = $row->application_id;
			}
			
			foreach($generate_accounts as $application_id)
			{
				try
				{
					$this->log->Write("Regenerating schedule for Active account with no scheduled events [{$application_id}]");
					Complete_Schedule($application_id);
				}
				catch (Exception $e)
				{
					$this->log->Write("Unable to regenerate schedule for {$application_id}: {$e->getMessage()}");
					throw $e;
				}
			}
		}
		
		
		public function getRuleComponentId($name_short)
		{
			$name_short = $this->db->quote($name_short);
			
			$query = "    -- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
			SELECT 	rule_component_id
			FROM 	rule_component 
			WHERE 	name_short = $name_short ";
			
			if($result = $this->db->Query($query))
			{
				if($result->rowCount() === 0) return FALSE;
				
				return $result->fetch(PDO::FETCH_OBJ)->rule_component_id;
			}
		}
		
		public function getRuleComponentParmId($rule_component_id, $parm_name)
		{
			$rule_component_id = $this->db->quote($rule_component_id);
			$parm_name         = $this->db->quote($parm_name);
			
			$query = "    -- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
			SELECT 	rule_component_parm_id
			FROM	rule_component_parm
			WHERE	rule_component_id = $rule_component_id
			AND		parm_name = $parm_name ";

			if($result = $this->db->Query($query))
			{
				if($result->rowCount() === 0) return FALSE;
				
				return $result->fetch(PDO::FETCH_OBJ)->rule_component_parm_id;
			}
		}
		
		public function getRuleSetsValues($rule_component_id, $rule_component_parm_id, $company_id)
		{
			$company_id             = $this->db->quote($company_id);
			$rule_component_id      = $this->db->quote($rule_component_id);
			$rule_component_parm_id = $this->db->quote($rule_component_parm_id);

			$query = "    -- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
			SELECT 	rscpv.rule_set_id,
					rscpv.parm_value
			FROM 	rule_set_component_parm_value AS rscpv
			JOIN 	rule_set AS rs ON (rs.rule_set_id = rscpv.rule_set_id)
			JOIN 	loan_type AS lt ON (lt.loan_type_id = rs.loan_type_id)
			WHERE	rscpv.rule_component_id = $rule_component_id
			AND		rscpv.rule_component_parm_id = $rule_component_parm_id 
			AND 	lt.company_id = $company_id ";

			$rule_sets = array();
			
			if($result = $this->db->Query($query))
			{
				while($row = $result->fetch(PDO::FETCH_ASSOC))
				{
					$rule_sets[] = $row;
				}
			}
			
			return $rule_sets;
		}
	}

?>