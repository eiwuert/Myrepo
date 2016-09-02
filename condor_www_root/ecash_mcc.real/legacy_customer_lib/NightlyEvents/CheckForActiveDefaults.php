<?php

	//	$manager->Define_Task('Move_Hotfiles_To_Pending_Expired', 'move_hotfiles_to_pending_exp', $hotfile_timer, 'move_hotfiles_to_pending_exp', array($server, $today));

	class ECash_NightlyEvent_CheckForActiveDefaults extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'move_hotfiles_to_pending_exp'; //Replace with proper biz rule
		protected $timer_name = 'Check_For_Active_Defaults';
		protected $process_log_name = 'check_for_active_defaults';
		protected $use_transaction = FALSE;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			
			parent::__construct();
		}
		
		/**
		 * A wrapper for the function Move_Hotfiles_To_Pending_Expired()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
			$this->Check_For_Active_Defaults($this->server, $this->start_date);
		}

		/**
		 * Check for active apps with failed transactions in the past X days and no arrangements.
		 *
		 * @param Server $server
		 * @param string $run_date
		 */
		private function Check_For_Active_Defaults(Server $server, $run_date)
		{
			$holidays = Fetch_Holiday_List();
			$pdc = new Pay_Date_Calc_3($holidays);
			$biz_rules = new ECash_Business_Rules(ECash_Config::getMasterDbConnection());
		
			$loan_type = 'cso';
			$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($server->company, $loan_type);
			$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
			$rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);
			$period = ($rules['lender_default']) ? $rules['lender_default'] : 3; //default to 3 days
			$period_day = $pdc->Get_Business_Days_Backward($run_date, $period);
			$threshold = date("Y-m-d 00:00:00", strtotime($period_day));
			
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT 
						DISTINCT application_id 
					FROM 
						transaction_register as tr
					JOIN application as app USING (application_id)
					WHERE 
					app.application_status_id = (select application_status_id from application_status where name_short='active')
					AND tr.date_effective > '{$threshold}'
					AND tr.transaction_status = 'failed'
					";
			$result = $this->db->Query($query);
			
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				//$this->log->Write("[App: {$row->application_id}] Moving Apps Hotfiled longer than {$period} business days to Pending Expiration queue.");
				//move_to_automated_queue("Pending Expiration", $row->application_id , "" , time() , NULL);
				//Update_Status(null, $row->application_id, "pend_expire::underwriting::applicant::*root", NULL, FALSE);
				if($this->hasArrangements($row->application_id))
				{
					$ecash_api = eCash_API_2::Get_eCash_API($this->company, $this->db, $row->application_id);
					//Get Fees
					$bank_fee = $ecash_api->getLenderBankFee($this->company, $loan_type);
					$late_fee = $ecash_api->getLenderLateFee($this->company, $loan_type);
					
					$amounts = array();
					$amounts[] = Event_Amount::MakeEventAmount('fee', $bank_fee);
					$e = Schedule_Event::MakeEvent($date_event, $date_event, $amounts, 'lend_assess_fee_ach','Lender Bank Fee Assessed');
					Post_Event($row->application_id, $e);
					
					$amounts = array();
					$amounts[] = Event_Amount::MakeEventAmount('fee', $late_fee);
					$e = Schedule_Event::MakeEvent($date_event, $date_event, $amounts, 'lend_assess_fee_late','Lender Late Fee Assessed');
					Post_Event($row->application_id, $e);
					
					//Update to Default status
					Update_Status(null, $row->application_id, array('default','collections','customer','*root'), NULL, NULL, FALSE);
				}
			}
		}
		
		private function hasArrangements($application_id)
		{
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT  et.name_short as `event_type`,
						es.event_schedule_id,
						es.application_id,
						a.application_status_id,
						es.date_event,
						es.date_effective,
						tt.clearing_type
				FROM    event_schedule AS es
				JOIN    event_type AS et USING (event_type_id)
				JOIN    (SELECT event_type_id, transaction_type_id FROM event_transaction GROUP BY event_type_id) AS evt USING (event_type_id)
				JOIN    transaction_type AS tt ON (tt.transaction_type_id = evt.transaction_type_id)
				JOIN    application AS a ON (a.application_id = es.application_id)
				WHERE   es.context = 'arrangement'
				AND     es.event_status = 'scheduled'
				AND		es.company_id = {$this->company_id}
				AND es.application_id = {$application_id}
					";
			$result = $this->db->Query($query);
			
			if($row = $result->fetch(PDO::FETCH_OBJ))
			{
				return true;
			}
			else 
			{
				return false;
			}
		}

	}

?>