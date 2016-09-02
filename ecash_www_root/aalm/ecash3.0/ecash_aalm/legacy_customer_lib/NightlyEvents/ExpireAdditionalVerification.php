<?php

	//	$manager->Define_Task('Expire_Additional_Verification', 'expire_additional_verification', $addl_ver_timer, 'expire_additional_verification', array($server, $today));

	class ECash_NightlyEvent_ExpireAdditionalVerification extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'expire_additional_verification';
		protected $timer_name = 'Expire_Additional_Verification';
		protected $process_log_name = 'expire_additional_verification';
		protected $use_transaction = FALSE;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			
			parent::__construct();
		}
		
		/**
		 * A wrapper for the function Expire_Additional_Verification()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
			$this->Expire_Additional_Verification($this->server, $this->start_date);
		}
		/**
		 * Move Addl. Verification apps older than 3 business days to Withrawn Status
		 *
		 * @param Server $server
		 * @param unknown_type $run_date
		 */
		private function Expire_Additional_Verification(Server $server, $run_date)
		{
			$holidays = Fetch_Holiday_List();
			$pdc = new Pay_Date_Calc_3($holidays);
			$biz_rules = new ECash_Business_Rules(ECash::getMasterDb());
		
			$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($server->company, 'offline_processing');
			$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
			$rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);
			
			$period = $rules['addl_ver_expiration'];
			$period_day = $pdc->Get_Business_Days_Backward($run_date, $period);
			$hotfile_threshold = date("Y-m-d 00:00:00", strtotime($period_day));
			
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT 
						application_id 
					FROM 
						application
					WHERE 
						application_status_id = (select application_status_id from application_status where name_short='addl')
					AND date_application_status_set < '{$hotfile_threshold}'
					";
			$result = $this->db->Query($query);
			
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$this->log->Write("[App: {$row->application_id}] Moving 3 day old Addl. Verification Apps to Withdrawn status.");
				remove_from_automated_queues($row->application_id);
				Update_Status(null, $row->application_id, "withdrawn::applicant::*root", NULL, FALSE);
			}
		}
	}

?>