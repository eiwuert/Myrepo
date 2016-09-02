<?php

	//		$manager->Define_Task('Resolve_Past_Due_To_Active', 'resolve_past_due_to_active', $rpdta_timer, 'resolve_past_due', array($server, $start_effective_date, $today));

	class ECash_NightlyEvent_ResolvePastDueToActive extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'resolve_past_due_to_active';
		protected $timer_name = 'Resolve_Past_Due_To_Active';
		protected $process_log_name = 'resolve_past_due';
		protected $use_transaction = FALSE;

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();
		}

		/**
		 * A wrapper for the function Resolve_Past_Due_To_Active()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();

			$this->Resolve_Past_Due_To_Active($this->start_date, $this->end_date);
		}

		/*
		 * This function should look for the individuals who are in past due
		 * and if their most recent payment events are NOT failures or still pending, they should be moved back into
		 * active/servicing/customer status.
		 *
		 * Issue: For manually resolved conversion apps, they will immediately meet this criteria. Can we find anything
		 *        stronger?
		 *
		 * Answer: We need to look into their schedule, and determine what the last non-scheduled items are:
		 *         1) If they are debits AND complete, move them. They paid.
		 *         2) If they are anything else, do nothing.
		 */
		private function Resolve_Past_Due_To_Active($start_date, $end_date)
		{
			// First, grab all people under Past Due
			$select_query = "
				SELECT
					ap.application_id as 'application_id'
				FROM
					application ap,
					application_status_flat asf
				WHERE
					(asf.level0='past_due' AND asf.level1='servicing'
				 	AND asf.level2='customer' AND asf.level3='*root')
				AND	ap.application_status_id = asf.application_status_id
				AND	ap.company_id = {$this->company_id}
			";

			$results = $this->db->query($select_query);

			while ($row = $results->fetch(PDO::FETCH_OBJ))
			{
				// For each one, find their last transactions and see if they
				// fit the criteria: any non-zero debits are completed.
				$select2_query = "
					SELECT transaction_status, amount, date_effective,
		       	transaction_register_id, transaction_type_id
					FROM transaction_register
					WHERE application_id = {$row->application_id}
						AND date_effective = (
							SELECT MAX(date_effective)
							FROM transaction_register
				      WHERE application_id = {$row->application_id}
				    )
						AND amount < 0.00
				";
				$rs2 = $this->db->query($select2_query);

				$reset_to_active = false;

				while ($row2 = $rs2->fetch(PDO::FETCH_OBJ))
				{
					if ($row2->transaction_status != 'complete')
					{
						$reset_to_active = false;
						break;
					}
					else
					{
						$reset_to_active = true;
					}
				}

				if (!$reset_to_active) continue;

				try
				{
					$this->log->Write("Set application {$row->application_id} from Past Due to Active.");
					Update_Status(NULL, $row->application_id, 'active::servicing::customer::*root', null, null, true);
				}
				catch (Exception $e)
				{
					$this->log->Write("Setting application {$row->application_id} Past Due -> Active failed.");
					throw $e;
				}
			}

			return true;
		}
	}


?>
