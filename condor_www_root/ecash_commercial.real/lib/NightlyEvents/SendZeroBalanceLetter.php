<?php

	//	$manager->Define_Task('Set_Completed_Accounts_To_Inactive', 'completed_accounts_to_inactive', $scati_timer, 'set_inactive', array($server));

	class ECash_NightlyEvent_SendZeroBalanceLetter extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'send_zbletter';
		protected $timer_name = 'SendZeroBalanceLetter';
		protected $process_log_name = 'send_zbletter';
		protected $use_transaction = FALSE;

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();
		}

		/**
		 * A wrapper for the function Set_Completed_Accounts_To_Inactive()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();

			$this->SendZeroBalanceLetters($this->server);
		}

		private function SendZeroBalanceLetters(Server $server)
		{
			$holidays  = Fetch_Holiday_List();
			$pdc       = new Pay_Date_Calc_3($holidays);
			$biz_rules = new ECash_BusinessRulesCache($this->db);

			$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
			$rule_set_id  = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
			$rules        = $biz_rules->Get_Rule_Set_Tree($rule_set_id);
			$status_query = "
				SELECT application_status_id, level0, level1
				FROM application_status_flat
				WHERE 	(level0='paid'   		AND level1='customer'    AND level2='*root' )
					OR      (level0='recovered' 		AND level1='external_collections'    AND level2='*root')

			";

			$results = $this->db->query($status_query);

			$statuses = array();
			while ($row = $results->fetch(PDO::FETCH_OBJ))
			{
				$statuses[$row->application_status_id] = $row;
			}

			$days = $rules['zbletter_wait'];
			if(empty($days))
			{
				$days = 0;
			}
			$check_date = $pdc->Get_Business_Days_Backward(date("Y-m-d"), $days);
			// Select every application which...
			// went inactive X business days before today
			$main_query = "
				SELECT
					a1.application_id,
					a1.application_status_id,
					a1.is_react,
					a1.date_modified
				FROM application AS a1
				WHERE a1.application_status_id IN (". implode(",", array_keys($statuses)) .")
					AND a1.company_id = {$this->company_id}
					AND DATE_FORMAT(a1.date_application_status_set, '%Y-%m-%d') = DATE_FORMAT('{$check_date}', '%Y-%m-%d')
			";
			$results = $this->db->query($main_query);

			while ($row = $results->fetch(PDO::FETCH_OBJ))
			{
				//Send the Zero balance letter
				$this->log->Write("Application {$row->application_id}: Sending Zero Balance Letter");
				eCash_Document_AutoEmail::Queue_For_Send($server, $row->application_id, 'ZERO_BALANCE_LETTER');

			}
		}


	}


?>
