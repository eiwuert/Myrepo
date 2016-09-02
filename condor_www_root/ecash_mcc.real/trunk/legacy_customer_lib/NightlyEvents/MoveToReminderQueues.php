<?php

	//	$manager->Define_Task('Move_To_Reminder_Queues', 'move_to_reminder_queues', $remind_queue_timer, 'move_to_reminder_queues', array($server, $today));

	class ECash_NightlyEvent_MoveToReminderQueues extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'move_to_reminder_queues';
		protected $timer_name = 'Move_To_Reminder_Queues';
		protected $process_log_name = 'move_to_reminder_queues';
		protected $use_transaction = FALSE;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			
			parent::__construct();
		}
		
		/**
		 * A wrapper for the function Move_To_Reminder_Queues()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
			$this->Move_To_Reminder_Queues($this->today);
			$this->cleanupReminderQueues($this->today);
		}

		/**
		 * Move Apps with due date a defined number of days away from today to a reminder queue.
		 *
		 * @param string $run_date (Y-m-d)
		 */
		private function Move_To_Reminder_Queues($run_date)
		{
			require_once(SQL_LIB_DIR . "react.func.php");
		
			$holidays = Fetch_Holiday_List();
			$pdc = new Pay_Date_Calc_3($holidays);
			$biz_rules = new ECash_Business_Rules(ECash_Config::getMasterDbConnection());
		
			$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
			$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
			$rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);
			
			$period = ($rules['reminder_queue_interval']) ? $rules['reminder_queue_interval'] : 2; // default to 2 if rule not set
			++$period; //Add one to period because nightly runs at the end of day, instead of next day[AGEAN LIVE 4216]
			$period_end = $pdc->Get_Business_Days_Forward($run_date, $period);
			$period_start = $pdc->Get_Business_Days_Forward($run_date, $period - 1);
			$reminder_end = date("Y-m-d 00:00:00", strtotime($period_end));
			$reminder_begin = date("Y-m-d 00:00:00", strtotime($period_start));
		
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
						SELECT
						    es.application_id,
						    app.company_id,
						    UNIX_TIMESTAMP(es.date_effective) as due_date,
						    UNIX_TIMESTAMP(es.date_event) as action_date,
						    lt.name_short as loan_type,
						    IFNULL((
						        SELECT COUNT(es2.event_schedule_id)
						        FROM event_schedule AS es2
						        JOIN event_amount AS ea ON (ea.event_schedule_id = es2.event_schedule_id)
						        JOIN event_amount_type AS eat ON (eat.event_amount_type_id = ea.event_amount_type_id)
						        JOIN event_type AS et2 ON (et2.event_type_id = es2.event_type_id)
						        WHERE es2.application_id = es.application_id
						        AND es2.event_status = 'registered'
						        AND et2.name_short IN ('payment_service_chg', 'repayment_principal')
						        AND ea.amount < 0
						        GROUP BY es2.application_id
						    ),0) AS num_registered_payments
						FROM
						    event_schedule AS es
						JOIN event_type AS et ON (et.event_type_id = es.event_type_id)
						JOIN application AS app ON (app.application_id = es.application_id)
						JOIN loan_type AS lt ON (lt.loan_type_id = app.loan_type_id)
						JOIN application_status AS apstat ON (app.application_status_id = apstat.application_status_id)
						WHERE   es.event_status = 'scheduled'
						AND     et.name_short IN ('payment_service_chg', 'repayment_principal')
						AND     es.date_effective BETWEEN '{$reminder_begin}' AND '{$reminder_end}'
						AND     es.date_effective > NOW()
						AND     app.company_id = {$this->company_id}
						AND     apstat.name = 'Active' 
						GROUP BY application_id
						HAVING num_registered_payments = 0
						ORDER BY es.date_effective ASC ";

			$result = $this->db->Query($query);
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				
				switch($row->loan_type)
				{
					case "delaware_title":
						$queue_name = "AT Reminder Queue";
					break;
					case "delaware_payday":
					case "california_payday":
						$queue_name = "PD Reminder Queue";
					break;
					default:
						$queue_name = NULL;
					break;
				}
				
				$reacts = Get_Reacts_From_App($row->application_id, $this->company_id);
				// The idea here is to make the due date count in opposite order to the react count.
				// this creates an aggregate key that will allow to sort the proper direction combination
				// (date_due is always on a day boundary)
				// if day stamp was : 99999 and reacts 3 yields 9999-3 which is lower than 9999-2.  default
				// ascending order places the 3 react before the 2 react that has the same due date, fulfilling spec.
				
				// Changed this to the action date because it's not going to do any good if the agents contacts
				// the customer after this date as they will not be able to make new arrangements after the 
				// transaction has already gone out. - BR
				$sort_string = $row->action_date + count($reacts);
		
				if($queue_name)
				{
					$this->log->Write("[App: {$row->application_id}] Adding App into queue: {$queue_name}.");
					queue_push($queue_name, $row->application_id , $sort_string , time() , NULL);
				}
			}
		}
		
		/**
		 * This method is run to remove applications from the Reminder queues after their
		 * payments have already been made.  This will keep applications from recycling
		 * indefinately.
		 * 
		 * This will require two passes.  The first by going through the queue table, and then
		 * the second by going through the current_queue_status table.
		 *
		 * @param string $run_date (Y-m-d)
		 */
		private function cleanupReminderQueues($run_date)
		{
			$this->log->Write("Starting cleanup for Reminder queues");
			$stamp = strtotime($run_date);
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
						SELECT 	key_value,
								queue_name,
						        FROM_UNIXTIME(sortable) AS sortable
						FROM queue 
						WHERE sortable != '' 
						AND sortable < UNIX_TIMESTAMP() 
						AND (queue_name = 'AT Reminder Queue' OR queue_name = 'PD Reminder Queue')";
			$result = $this->db->Query($query);
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$this->log->Write("Removing {$row->application_id} from queue ({$row->queue_name}) since event date {$row->sortable} has passed");
				queue_remove($row->queue_name, $row->application_id);
			}

			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
						SELECT 	application_id, 
								queue_name,
						        FROM_UNIXTIME(sortable) AS sortable
						FROM current_queue_status
						WHERE sortable != ''
						AND sortable < UNIX_TIMESTAMP() 
						AND (queue_name = 'AT Reminder Queue' OR queue_name = 'PD Reminder Queue')";
			$result = $this->db->Query($query);
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$this->log->Write("Removing {$row->application_id} from current queue stats ({$row->queue_name}) since event date {$row->sortable} has passed");
				current_queue_status_reset($row->application_id, $row->queue_name);
			}
			$this->log->Write("Finished cleanup of Reminder queues");
		}
	}

?>