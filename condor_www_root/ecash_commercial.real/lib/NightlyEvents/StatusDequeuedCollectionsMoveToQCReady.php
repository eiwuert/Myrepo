<?php

	//	$manager->Define_Task('Status_Dequeued_Collections_Move_To_QC_Ready', 'deq_coll_to_qc_ready', $status_dequeued_timer, 'collections_qc', array($server));

	class ECash_NightlyEvent_StatusDequeuedCollectionsMoveToQCReady extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'deq_coll_to_qc_ready';
		protected $timer_name = 'Status_Dequeued_Collections_Move_To_QC_Ready';
		protected $process_log_name = 'collections_qc';
		protected $use_transaction = FALSE;

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();
		}

		/**
		 * A wrapper for the function Status_Dequeued_Collections_Move_To_QC_Ready()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();

			$this->Status_Dequeued_Collections_Move_To_QC_Ready($this->server);
		}

		/**
		 * Moves apps with collections/dequeued and fatal ACH code to QC Ready
		 * Looks for apps in the standby table to move.
		 */
		private function Status_Dequeued_Collections_Move_To_QC_Ready()
		{
			// If we're not using QuickChecks, disable QC Related activities
			if(eCash_Config::getInstance()->USE_QUICKCHECKS === FALSE) return TRUE;

			// All the statuses to include in the update
			$query = "
					SELECT asf.application_status_id
					FROM application_status_flat asf
					WHERE (asf.level0 = 'return'
					AND asf.level1 = 'quickcheck' AND asf.level2 = 'collections'
					AND asf.level3 = 'customer' AND asf.level4 = '*root') OR
					((asf.level0 = 'queued' OR asf.level0 = 'dequeued'OR asf.level0 = 'follow_up')
					AND asf.level1 = 'contact' AND asf.level2 = 'collections'
					AND asf.level3 = 'customer' AND asf.level4 = '*root') ";

			$statuses = $this->db->querySingleColumn($query);

			// First we pull up anyone still eligible.
			$sql = "
        SELECT st.application_id
        FROM standby st,
        	application app,
        	application_status_flat asf
        WHERE st.process_type = 'qc_ready'
        	AND DATE_ADD(st.date_created, INTERVAL 1 day) < CURRENT_TIMESTAMP
        	AND app.company_id = {$this->company_id}
        	AND app.application_status_id = asf.application_status_id
        	AND asf.application_status_id IN ( " .implode(",", $statuses) . ")
        	AND app.application_id = st.application_id
      ";
			$st = $this->db->query($sql);

			while($row = $st->fetch(PDO::FETCH_OBJ))
			{
				// Gotta set this before running Update_Status
				$_SESSION['LOCK_LAYER']['App_Info'][$row->application_id]['date_modified'] = $row->date_modified;
				try
				{
					$this->log->Write("Application {$row->application_id}: Collections Dequeued -> QC Ready");
					Update_Status(NULL, $row->application_id, array( 'ready', 'quickcheck','collections','customer', '*root' ));
					Remove_Standby($row->application_id, 'qc_ready');

				}
				catch (Exception $e)
				{
					$this->log->Write("Movement of Collections Dequeued app {$row->application_id} to QC Ready failed.");
					throw $e;
				}
			}

      // Now we remove anyone who's not in queued/dequeued/followup Collections
      // b/c they've been taken care of and we don't want to bloat the table.
      $sql = "
				SELECT st.application_id
				FROM standby st,
					application app,
					application_status_flat asf
				WHERE st.process_type = 'qc_ready'
					AND app.application_id = st.application_id
					AND app.company_id = {$this->company_id}
					AND app.application_status_id = asf.application_status_id
					AND asf.application_status_id NOT IN (" .implode(",", $statuses) . ")
			";
      $st = $this->db->Query($sql);

      while ($row = $st->fetch(PDO::FETCH_OBJ))
      {
        $this->log->Write("Removing standby entry for ({$row->application_id}, 'qc_ready')");
        Remove_Standby($row->application_id, 'qc_ready');
      }
		}
	}

?>