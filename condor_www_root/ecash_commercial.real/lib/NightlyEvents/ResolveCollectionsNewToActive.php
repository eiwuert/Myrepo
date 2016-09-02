<?php

	//	$manager->Define_Task('Resolve_Collections_New_To_Active', 'resolve_collections_new_to_act', $rcnta_timer, 'resolve_collections_new', array($server, $start_effective_date, $today));

	class ECash_NightlyEvent_ResolveCollectionsNewToActive extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'resolve_collections_new_to_act';
		protected $timer_name = 'Resolve_Collections_New_To_Active';
		protected $process_log_name = 'resolve_collections_new';
		protected $use_transaction = FALSE;

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();
		}

		/**
		 * A wrapper for the function Resolve_Collections_New_To_Active()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();

			$this->Resolve_Collections_New_To_Active($this->start_date, $this->end_date);
		}

		/* This function is similar to Resolve_Past_Due_To_Active but it works with apps in the
		 * Collections New status.  What we want to do is check to see if their account is in good
		 * standing and that they haven't used any arrangements to get there.
		 *
		 */
		private function Resolve_Collections_New_To_Active($start_date, $end_date)
		{
			$col_new_id = Status_Utility::Get_Status_ID_By_Chain('new::collections::customer::*root');

			// First, grab all people under Past Due
			$select_query = '
			-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT
				ap.application_id as 'application_id'
			FROM
				application ap
			WHERE
				ap.application_status_id = $col_new_id AND
				ap.company_id = {$this->company_id} AND
				NOT EXISTS (
					SELECT 1
					FROM
						event_schedule es
					WHERE
						es.application_id = ap.application_id 
					AND
						(
						es.context = 'arrangement'
							OR
								es.context = 'partial'
						)
				)
			";
			$st = $this->db->query($select_query);

			while ($app = $st->fetch(PDO::FETCH_OBJ))
			{
				// For each one, find their last transactions and see if they
				// fit the criteria: any non-zero debits are completed.
				$query = "
					SELECT transaction_status, amount, date_effective,
				       	transaction_register_id, transaction_type_id
					FROM transaction_register
					WHERE application_id = {$app->application_id}
					AND date_effective = (SELECT MAX(date_effective)
					      		FROM transaction_register
						      	WHERE application_id = {$app->application_id})
					AND amount < 0.00
				";
				$st2 = $this->db->query($query);

				$reset_to_active = false;

				while ($row = $st2->fetch(PDO::FETCH_OBJ))
				{
					if ($row->transaction_status != 'complete')
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
					$this->log->Write("Set application {$app->application_id} from Collections New to Active.");
					Update_Status(NULL, $app->application_id, 'active::servicing::customer::*root');
				}
				catch (Exception $e)
				{
					$this->log->Write("Setting application {$app->application_id} Collections New -> Active failed.");
					throw $e;
				}
			}

			return true;
		}
	}

?>
