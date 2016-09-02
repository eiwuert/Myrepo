<?php

	//	$manager->Define_Task('Withdraw_Soft_Fax', 'withdraw_soft_fax', $softfax_wd_timer, 'withdraw_soft_fax', array($server, $today));

	class ECash_NightlyEvent_WithdrawSoftFax extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'withdraw_soft_fax';
		protected $timer_name = 'Withdraw_Soft_Fax';
		protected $process_log_name = 'withdraw_soft_fax';
		protected $use_transaction = FALSE;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			
			parent::__construct();
		}
		
		/**
		 * A wrapper for the function Withdraw_Soft_Fax()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
			$this->Withdraw_Soft_Fax($this->today);
		}

		/**
		 * Move soft fax apps to withdrawn status after a business rule defined number of days.
		 *
		 * @param string $run_date
		 */
		private function Withdraw_Soft_Fax($run_date)
		{
			$holidays = Fetch_Holiday_List();
			$pdc = new Pay_Date_Calc_3($holidays);
			$biz_rules = new ECash_Business_Rules(ECash_Config::getMasterDbConnection());
		
			$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
			$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
			$rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);
			
			$period = ($rules['softfax_expiration']) ? $rules['softfax_expiration'] : 2;
			$run_date = date("Y-m-d");
			$period_day = $pdc->Get_Business_Days_Backward($run_date, $period);
			$softfax_threshold = date("Y-m-d 00:00:00", strtotime($period_day));
			
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT 
						application_id 
					FROM 
						application
					WHERE 
						application_status_id = (select application_status_id from application_status where name_short='soft_fax')
					AND date_application_status_set < '{$softfax_threshold}'
					";
			$result = $this->db->Query($query);
		
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$this->log->Write("[App: {$row->application_id}] Moving Apps in Softfax status longer than {$period} business days to Withdrawn status.");
				Update_Status(null, $row->application_id, "withdrawn::applicant::*root", NULL, NULL, TRUE);
			}
		}
	}

?>