<?php

	//	$manager->Define_Task('Move_Arrangements_To_MyQueue', 'move_arrangements_to_myqueue', $myqueue_timer, 'move_arrangements_to_myqueue', array($server, $today));

	class ECash_NightlyEvent_MoveArrangementsToMyQueue extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'move_arrangements_to_myqueue';
		protected $timer_name = 'Move_Arrangements_To_MyQueue';
		protected $process_log_name = 'move_arrangements_to_myqueue';
		protected $use_transaction = FALSE;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			
			parent::__construct();
		}
		
		/**
		 * A wrapper for the function Move_Arrangements_To_MyQueue()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
			$this->Move_Arrangements_To_MyQueue($this->today);
		}

		/**
		 * Move apps with a controlling agent and payment arrangements to My Queue a number of days before due date.
		 *
		 * @param string $run_date
		 */
		private function Move_Arrangements_To_MyQueue($run_date)
		{
			$holidays = Fetch_Holiday_List();
			$pdc = new Pay_Date_Calc_3($holidays);
			
			$biz_rules = new ECash_Business_Rules(ECash::getMasterDb());
		
			$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
			$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
			$rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);
			
			$period = ($rules['arrangements_to_myqueue_interval']) ? $rules['arrangements_to_myqueue_interval'] : 1;  // This will be THREE days on the next morning
		
			$period_day = $pdc->Get_Business_Days_Forward($run_date, $period);
			$period_date = date('Ymd', strtotime($period_day));
			$today = date('Ymd');
			
			// We're looking for arrangements that are due on the period_date 
			// that aren't adjustments and have an assoicated agent
			$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT  et.name_short as `event_type`,
						es.event_schedule_id,
						es.application_id,
						a.application_status_id,
						es.date_event,
						es.date_effective,
						aa.agent_id,
						tt.clearing_type
				FROM    event_schedule AS es
				JOIN    event_type AS et USING (event_type_id)
				JOIN    (SELECT event_type_id, transaction_type_id FROM event_transaction GROUP BY event_type_id) AS evt USING (event_type_id)
				JOIN    transaction_type AS tt ON (tt.transaction_type_id = evt.transaction_type_id)
				JOIN 	agent_affiliation_event_schedule AS aaes USING (event_schedule_id)
				JOIN 	agent_affiliation AS aa USING (agent_affiliation_id)
				JOIN    application AS a ON (a.application_id = es.application_id)
				WHERE   ( es.context = 'arrangement' OR es.context = 'partial' )
				AND     es.event_status = 'scheduled'
				AND     es.date_effective = '{$period_date}'
				AND		es.company_id = '{$this->company_id}'
				HAVING clearing_type <> 'adjustment'
				ORDER BY event_schedule_id ";
		
			$result = $this->db->Query($sql);
			
			$applications = array();
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				if(!empty($row->agent_id)) {
					// Set Follow-Up time by updating the status
					$this->log->Write("Updating Arrangement follow up time for App: {$row->application_id}, Agent: {$row->agent_id}");
					Update_Status(NULL, $row->application_id, $row->application_status_id, $today, $row->agent_id, FALSE);
				}
			}
		}

	}

?>
