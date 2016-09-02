<?php

	//	$manager->Define_Task('Set_Completed_Accounts_To_Inactive', 'completed_accounts_to_inactive', $scati_timer, 'set_inactive', array($server));

	class ECash_NightlyEvent_SetCompletedAccountsToInactive extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'completed_accounts_to_inactive';
		protected $timer_name = 'Set_Completed_Accounts_To_Inactive';
		protected $process_log_name = 'set_inactive';
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

			$this->Set_Completed_Accounts_To_Inactive($this->server);
		}

		private function Set_Completed_Accounts_To_Inactive(Server $server)
		{
			$status_query = "
				SELECT application_status_id, level0, level1
				FROM application_status_flat
				WHERE 	(level0='active'   		AND level1='servicing'    AND level2='customer'    AND level3='*root')
					OR      (level0='past_due' 		AND level1='servicing'    AND level2='customer'    AND level3='*root')
					OR      (level0='new'      		AND level1='collections'  AND level2='customer'    AND level3='*root')
					OR      (level0='indef_dequeue' AND level1='collections'  AND level2='customer'    AND level3='*root')
					OR      (level0='queued'   		AND level1='contact'      AND level2='collections' AND level3='customer' AND level4='*root')
					OR      (level0='dequeued' 		AND level1='contact'      AND level2='collections' AND level3='customer' AND level4='*root')
					OR		(level0='follow_up'		AND level1='contact'      AND level2='collections' AND level3='customer' AND level4='*root')
					OR      (level0='sent'     		AND level1='quickcheck'   AND level2='collections' AND level3='customer' AND level4='*root')
					OR      (level0='current'  		AND level1='arrangements' AND level2='collections' AND level3='customer' AND level4='*root')
					OR      (level0='sent'     		AND level1='external_collections' AND level2 = '*root')
					OR      (level0='approved'  	AND level1='servicing' AND level2 = 'customer' and level3 = '*root')
			";

			$results = $this->db->query($status_query);

			$statuses = array();
			while ($row = $results->fetch(PDO::FETCH_OBJ))
			{
				$statuses[$row->application_status_id] = $row;
			}

			$supplemental_query = "
				SELECT
					application_id,
					(
						SELECT count(tr.transaction_register_id)
						FROM transaction_register tr, transaction_type tt
						WHERE tr.application_id = app.application_id
							AND tr.transaction_type_id = tt.transaction_type_Id
							AND tt.name_short LIKE 'cancel%'
							AND tr.transaction_status = 'complete'
					) AS cancel_complete,
					(
						SELECT count(tr.transaction_register_id)
						FROM transaction_register tr, transaction_type tt
						WHERE tr.application_id = app.application_id
							AND tr.transaction_type_id = tt.transaction_type_Id
							AND tt.name_short = 'payment_service_chg'
							AND tr.transaction_status = 'complete'
					) AS paid_service_charges,
					(
						SELECT count(tr.transaction_register_id)
						FROM transaction_register tr
						WHERE tr.application_id = app.application_id
							
					) AS num_transactions
				FROM application app, application_status_flat asf
				WHERE application_id = ?
					AND app.application_status_id = asf.application_status_id
			";
			$info = $this->db->prepare($supplemental_query);

			// Select every application which...
			// Has only completed transactions (No new or pending transactions, and no scheduled events)
			// Has a balance of zero (see tr2, group by, having)
			// Has a valid status (see above query injected below)
			$main_query = "
				SELECT
					a1.application_id,
					a1.application_status_id,
					a1.is_react,
					a1.date_modified
				FROM application AS a1
				LEFT JOIN transaction_register AS tr1 ON (tr1.application_id = a1.application_id
						AND tr1.transaction_status IN ('new','pending'))
				LEFT JOIN event_schedule AS es1 ON (es1.application_id = a1.application_id
						AND es1.event_status = 'scheduled')
				LEFT JOIN transaction_register AS tr2 ON (tr2.application_id = a1.application_id
						AND tr2.transaction_status IN ('complete'))
				WHERE a1.application_status_id IN (". implode(",", array_keys($statuses)) .")
					AND a1.company_id = {$this->company_id}
					AND tr1.transaction_register_id IS NULL
					AND es1.event_schedule_id IS NULL
	            AND (tr2.transaction_type_id not in (
	                SELECT transaction_type_id
	                FROM transaction_type
	                WHERE name_short like '%refund_3rd_party%'
	          ) or tr2.transaction_type_id is null)
				GROUP BY a1.application_id
				HAVING  SUM(IFNULL(tr2.amount,0)) <= 0 
			";
			
			$results = $this->db->query($main_query);

			while ($row = $results->fetch(PDO::FETCH_OBJ))
			{
				// Gotta set this before running Update_Status
				$_SESSION['LOCK_LAYER']['App_Info'][$row->application_id]['date_modified'] = $row->date_modified;
				if ($statuses[$row->application_status_id]->level0 == 'sent' &&
					$statuses[$row->application_status_id]->level1 == 'external_collections')
				{
					$this->log->Write("Application {$row->application_id}: Setting to Recovered.");
					$new_stat = array('recovered','external_collections','*root');
				}
				else
				{
					$info->execute(array($row->application_id));
					$row2 = $info->fetch(PDO::FETCH_OBJ);

					if (($row2->cancel_complete > 0) && ($row->is_react == 'no'))
					{
						if($row2->paid_service_charges > 0)
						{
							$this->log->Write("Application {$row->application_id}: Setting to Paid Inactive (Cancelled)");
							$new_stat = array('paid', 'customer', '*root');
							//Send the Zero balance letter
						//	eCash_Document_AutoEmail::Queue_For_Send($server, $row->application_id, 'ZERO_BALANCE_LETTER');
						}
						else
						{
							$this->log->Write("Application {$row->application_id}: Setting to Withdrawn (Cancelled)");
							$new_stat = array('withdrawn', 'applicant', '*root');
						}
					}
					elseif($row2->num_transactions == 0)
					{
						$this->log->Write("Application {$row->application_id}: Setting to Withdrawn (Cancelled)");
						$new_stat = array('withdrawn', 'applicant', '*root');
					}
					else
					{
						$this->log->Write("Application {$row->application_id}: Setting to Inactive.");
						$new_stat = array('paid','customer','*root');
				//		eCash_Document_AutoEmail::Queue_For_Send($server, $row->application_id, 'ZERO_BALANCE_LETTER');
					}
				}

				try
				{
					Remove_Unregistered_Events_From_Schedule($row->application_id);
					Update_Status(NULL, $row->application_id, $new_stat);

					$application = ECash::getApplicationById($row->application_id);
					$affiliations = $application->getAffiliations();
					$affiliations->expire('collections', 'owner');
					//[mantis:5060] [rlopez]
					Loan_Data::Activate_Pending_Preact($server,$row->application_id);
				}
				catch (Exception $e)
				{
					$this->log->Write("Movement of app {$row->application_id} to Inactive/Recovered failed.");
					throw $e;
				}
			}
		}


	}


?>