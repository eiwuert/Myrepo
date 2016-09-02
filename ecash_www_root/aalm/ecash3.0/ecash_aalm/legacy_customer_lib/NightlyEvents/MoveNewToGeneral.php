<?php
/* MoveNewToGeneral.php
 *
 * This event checks for applications that are in 'Collections New' status that have been
 * queued in the my queue for a business rule defined number of business days.
 *
 * This cron fulfills spec point 6.1.9 of the AALM Collections Specification
 * part of gForge ticket #13633.
 */

class ECash_NightlyEvent_MoveNewToGeneral extends ECash_Nightly_Event
{
	// Parameters used by the Cron Scheduler
	// this needs to be ran everyday, no use in setting up a business rule
	protected $business_rule_name = null; // 'move_delinquent_to_collections_rework';
	protected $timer_name = 'Move_New_To_General';
	protected $process_log_name = 'move_new_to_general';
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

		$this->Move_New_To_General($this->start_date, $this->end_date);
	}
	
	private function Move_New_To_General($start_date, $end_date)
	{
		$holidays  = Fetch_Holiday_List();
		$pdc       = new Pay_Date_Calc_3($holidays);
		$biz_rules = new ECash_BusinessRulesCache($this->db);

		$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'company_level');
		$rule_set_id  = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rules        = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

		$days = (isset($rules['collections_processes']['days_until_general'])) ? $rules['collections_processes']['days_until_general'] : 30;
		
		//Per Jared, changed to cumulative time the loan has been in status
		$query = "
			SELECT
				app.application_id,
				SUM(
					IF(sh1.date_created IS NULL,
					DATEDIFF(NOW(),sh.date_created),
					DATEDIFF(sh1.date_created,sh.date_created)
					)
				)AS total_time
			FROM
				application AS app
				JOIN authoritative_ids AS auth ON (auth.authoritative_id=app.application_id)
				JOIN application_status_flat AS asf_app ON (asf_app.application_status_id = app.application_status_id)
				JOIN status_history AS sh ON (sh.application_id = app.application_id)
				JOIN application_status_flat AS asf ON (asf.application_status_id = sh.application_status_id)
				LEFT JOIN status_history AS sh1 ON (sh1.application_id = sh.application_id
									AND sh1.status_history_id > sh.status_history_id)
				LEFT JOIN status_history AS sh2 ON (sh2.application_id = sh.application_id
									AND sh2.status_history_id > sh.status_history_id
									AND sh2.status_history_id < sh1.status_history_id)
			WHERE
				app.applicant_account_id > 0
				AND asf_app.level0 IN ('indef_dequeue','new') AND asf.level1 = 'collections'
				AND asf.level0 IN ('indef_dequeue','new') AND asf.level1 = 'collections'
				AND sh2.status_history_id IS NULL
				GROUP BY app.application_id
				HAVING total_time > {$days}
				ORDER BY sh.status_history_id
		";

		$results = $this->db->query($query);

		while ($row = $results->fetch(PDO::FETCH_OBJ))
		{
			try
			{
				// Send them to the collections general process
				$this->db->beginTransaction();

				Remove_Unregistered_Events_From_Schedule($row->application_id);

				// Send Return Letter 3 - 'Overdue Account Letter' 6.2.1.4
				//ECash_Documents_AutoEmail::Queue_For_Send($row->application_id, 'RETURN_LETTER_3_OVERDUE_ACCOUNT');
				ECash_Documents_AutoEmail::Queue_For_Send($row->application_id, 'PAYMENT_FAILED');

				// 6.2.1.1 - Change status to Collections Contact
				Update_Status(null, $row->application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, FALSE);

				// Remove from all Queues
				$qi = new ECash_Queues_BasicQueueItem($row->application_id);
				$qm = ECash::getFactory()->getQueueManager();
				$qm->removeFromAllQueues($qi);

				// 6.2.1.5 - Add to Collections General Queue
				$qm = ECash::getFactory()->getQueueManager();
				$queue_item = $qm->getQueue('collections_general')->getNewQueueItem($row->application_id);
				$qm->moveToQueue($queue_item, 'collections_general');

				$this->db->commit();

				$this->log->Write("Moved Application: {$row->application_id} to collections general process. [6.1.9 #13633]");
			}
			catch (Exception $e)
			{
				$this->db->rollback();

				$this->log->Write("FAILED moving application: {$row->application_id} to collections general process.");
				throw $e;
			}
		}

		return TRUE;
	}
}


?>
