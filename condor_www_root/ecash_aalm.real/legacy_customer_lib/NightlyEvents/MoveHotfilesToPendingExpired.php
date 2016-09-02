<?php

	//	$manager->Define_Task('Move_Hotfiles_To_Pending_Expired', 'move_hotfiles_to_pending_exp', $hotfile_timer, 'move_hotfiles_to_pending_exp', array($server, $today));

	class ECash_NightlyEvent_MoveHotfilesToPendingExpired extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'move_hotfiles_to_pending_exp';
		protected $timer_name = 'Move_Hotfiles_To_Pending_Expired';
		protected $process_log_name = 'move_hotfiles_to_pending_exp';
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
			
			$this->Move_Hotfiles_To_Pending_Expired($this->server, $this->start_date);
		}

		/**
		 * Move Hotfiled apps older than 5 business days to Pending Expired Queue
		 *
		 * @param Server $server
		 * @param string $run_date
		 */
		private function Move_Hotfiles_To_Pending_Expired(Server $server, $run_date)
		{
			$holidays = Fetch_Holiday_List();
			$pdc = new Pay_Date_Calc_3($holidays);
			$biz_rules = new ECash_Business_Rules(ECash_Config::getMasterDbConnection());
		
			$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($server->company, 'offline_processing');
			$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
			$rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);
			
			$period = ($rules['hotfile_expiration']) ? $rules['hotfile_expiration'] : 5; //default to 5 days
			$period_day = $pdc->Get_Business_Days_Backward($run_date, $period);
			$hotfile_threshold = date("Y-m-d 00:00:00", strtotime($period_day));
			
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT 
						application_id 
					FROM 
						application
					WHERE 
						application_status_id = (select application_status_id from application_status where name_short='hotfile')
					AND date_application_status_set < '{$hotfile_threshold}'
					";
			$result = $this->db->Query($query);
			
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$this->log->Write("[App: {$row->application_id}] Moving Apps Hotfiled longer than {$period} business days to Pending Expiration queue.");
				move_to_automated_queue("Pending Expiration", $row->application_id , "" , time() , NULL);
				Update_Status(null, $row->application_id, "pend_expire::underwriting::applicant::*root", NULL, FALSE);
			}
		}

	}

?>