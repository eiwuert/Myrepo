<?php

	//	$manager->Define_Task('Move_Hotfiles_To_Pending_Expired', 'move_hotfiles_to_pending_exp', $hotfile_timer, 'move_hotfiles_to_pending_exp', array($server, $today));

	class ECash_NightlyEvent_MoveOldCollectionsToChargeoff extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'move_old_collections_to_charge';
		protected $timer_name = 'Move_Old_Collections_To_Charge';
		protected $process_log_name = 'move_old_collections_to_charge';
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
			
			$this->Move_Old_Collections_To_Chargeoff($this->server, $this->start_date);
		}

		/**
		 * Move Hotfiled apps older than 5 business days to Pending Expired Queue
		 *
		 * @param Server $server
		 * @param string $run_date
		 */
		function Move_Old_Collections_To_Chargeoff(Server $server, $run_date)
		{
			require_once(SQL_LIB_DIR ."fetch_status_map.func.php");
		
			$db = ECash_Config::getMasterDbConnection();
			$log = $server->log;
		
			$status_map = Fetch_Status_Map();
			$collections_statuses = array();
			/* Spec: Business requirements: 1.) A charge-off for CRA reporting purposes will be defined as a loan 
				in a "collections" status that has aged 120 days from the first failed payment. */
			$new_status = Search_Status_Map('chargeoff::collections::customer::*root', $status_map);
		
			if (empty($new_status)) throw new Exception ("Unable to find status for chargeoff!");	
		
			$collections_statuses[] = Search_Status_Map('indef_dequeue::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('new::collections::customer::*root', $status_map);
		// skip trace may be needed if this is imported to other companies like agean, but it doesn't exist in impact.
		//	$collections_statuses[] = Search_Status_Map('skip_trace::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('arrangements_failed::arrangements::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('current::arrangements::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('hold::arrangements::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('amortization::bankruptcy::collections::customer::*root', $status_map);
			//$collections_statuses[] = Search_Status_Map('unverified::bankruptcy::collections::customer::*root', $status_map);
			//$collections_statuses[] = Search_Status_Map('verified::bankruptcy::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('dequeued::contact::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('follow_up::contact::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('queued::contact::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('arrangements::quickcheck::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('ready::quickcheck::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('return::quickcheck::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('sent::quickcheck::collections::customer::*root', $status_map);
			
			$company_id = $server->company_id;
			
			// We're looking for accounts in our application status set (collections tree) that have had more than 120 days since first failure
			// and have no scheduled entries at all
			$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT application_id
				FROM application
				JOIN (SELECT application_id, MIN(date_effective) AS first_failure 
					  FROM transaction_register 
					  WHERE transaction_status =  'failed'
					  GROUP BY application_id						 
					  HAVING DATE_ADD(first_failure, INTERVAL 120 DAY) < NOW() ) ff USING (application_id)
				JOIN (SELECT application_id, MAX(date_effective) AS last_failure 
					  FROM transaction_register 
					  WHERE transaction_status =  'failed'
					  GROUP BY application_id						 
					  HAVING DATE_ADD(last_failure, INTERVAL 14 DAY) < NOW() ) lf USING (application_id)
				LEFT OUTER JOIN (SELECT application_id, COUNT(*) AS scheduled_count
								 FROM event_schedule 
								 WHERE event_status = 'scheduled'
								 GROUP BY application_id) es USING (application_id)
				WHERE scheduled_count IS NULL
				  AND application_status_id IN (" . implode(',', $collections_statuses) . ") 
				  AND company_id = '{$company_id}'
				";
		
			$result = $db->Query($sql);
			
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$log->Write("[App: {$row->application_id}] Moving to status Chargeoff");
				Update_Status(null, $row->application_id, array('chargeoff','collections','customer','*root'),NULL,NULL,FALSE); // the false is for no-queues
				
				// Per Impact, ChargeOff accounts do not need to be worked, so remove them from any automated queues.
				//remove_from_automated_queues($row->application_id);
				$qm = ECash::getFactory()->getQueueManager();
				$qm->removeFromAllQueues(new ECash_Queues_BasicQueueItem($row->application_id));
			}
		}


	}

?>