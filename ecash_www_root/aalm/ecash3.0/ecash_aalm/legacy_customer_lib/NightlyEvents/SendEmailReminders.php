<?php

	//	$manager->Define_Task('Send_Email_Reminders', 'send_email_reminders', $send_remind_timer, 'send_email_reminders', array($server, $today));

	class ECash_NightlyEvent_SendEmailReminders extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'send_email_reminders';
		protected $timer_name = 'Send_Email_Reminders';
		protected $process_log_name = 'send_email_reminders';
		protected $use_transaction = FALSE;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			
			parent::__construct();
		}
		
		/**
		 * A wrapper for the function Send_Email_Reminders()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
			$this->Send_Email_Reminders($this->server, $this->start_date);
		}

		/**
		 * Send email reminders with due date a defined number of days away from today.
		 *
		 * @param Server $server
		 * @param string $run_date
		 */
		private function Send_Email_Reminders(Server $server, $run_date)
		{
			require_once(LIB_DIR."Document/Document.class.php");
			
			$holidays = Fetch_Holiday_List();
			$pdc = new Pay_Date_Calc_3($holidays);
			//$biz_rules = new ECash_Business_Rules(ECash::getMasterDb());
		
			//$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
			//$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
			//$rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);
			
			$email_reminder_interval = Company_Rules::Get_Config('email_reminder_interval');
			//$period = ($rules['email_reminder_interval']) ? $rules['email_reminder_interval'] : 4; // default to 4 if rule not set
			if (isset($email_reminder_interval))
			{
				$period = $email_reminder_interval;
			}
			else
			{
				$period = 2;
			}

			$period_date = $pdc->Get_Business_Days_Forward($run_date, $period);
			$reminder_date = date("Y-m-d", strtotime($period_date));
		
			$query = '
				-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT DISTINCT
					es.application_id
				FROM
					event_schedule AS es
				JOIN
					application AS ap ON (ap.application_id = es.application_id)
				JOIN
					event_amount AS ea ON (ea.event_schedule_id = es.event_schedule_id)
				JOIN
					event_transaction AS et ON (et.company_id = es.company_id
								AND et.event_type_id = es.event_type_id)
				JOIN
					transaction_type AS tt ON (tt.company_id = et.company_id
									AND tt.transaction_type_id = et.transaction_type_id)
				WHERE
					ea.amount < 0
				AND
					es.event_status = 'scheduled'
				AND
					tt.clearing_type IN ('ach','card','external')
				AND
					es.date_effective = '{$reminder_date}'
				AND
					es.date_effective > NOW()
				AND
					es.company_id = {$this->company_id}
				GROUP BY application_id
				ORDER BY es.date_effective ASC
			";
			$result = $this->db->Query($query);
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$this->log->Write("[App: {$row->application_id}] Sending Email Reminder for App {$period} business days prior due date.");
				ECash_Documents_AutoEmail::Queue_For_Send($row->application_id, 'PAYMENT_REMINDER');
			}
		}
	}

?>