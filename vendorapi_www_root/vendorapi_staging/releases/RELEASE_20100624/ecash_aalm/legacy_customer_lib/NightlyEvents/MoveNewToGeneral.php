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

		$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
		$rule_set_id  = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rules        = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

		$days = (isset($rules['collections']['days_until_general'])) ? $rules['collections']['days_until_general'] : 3;
		
		$end_stamp = $pdc->Get_Business_Days_Backward(date('Y-m-d'), $days);

		// Get the queue manager
		$qm = ECash::getFactory()->getQueueManager();
	
		// Get the my queue
		$agent_queue = $qm->getQueue('Agent');

		// get the my queue table
		$my_queue_table = $agent_queue->getQueueEntryTableName();

		$col_queue = $qm->getQueue('collections_new');
		$col_table = $col_queue->getQueueEntryTableName();
		
		$as_query = "EXECUTE sp_fetch_application_ids_by_application_status 'new::collections::customer::*root'";
		
		$result = ECash::getAppSvcDB()->query($as_query);
		ECash_DB_Util::generateTempTableFromArray($this->db, $this->getTempTableName(), $result->fetchAll(),
				$this->getTempTableSpec(), $this->getApplicationIdColumn());

		// Get all applications in 'collections new' in 'My Queue'
		// that have been in the queue for more than 3 days
		// OR
		// applications in collections new status that are not in
		// 'My Queue' or the 'Collections New' queue as a failsafe
		$query = "
			SELECT
				app.application_id
			FROM
				" . $this->getTempTableName() . " app
				LEFT JOIN {$my_queue_table} mqe
					ON mqe.related_id = app.application_id
				LEFT JOIN {$col_table} nqe
					ON nqe.related_id = app.application_id
			WHERE
				mqe.date_queued <= '{$end_stamp}'
				OR (nqe.date_queued IS NULL AND mqe.date_queued IS NULL)
		";

		$results = $this->db->query($query);

		while ($row = $results->fetch(PDO::FETCH_OBJ))
		{
			$schedule = Fetch_Schedule($row->application_id);
			$status   = Analyze_Schedule($schedule);

			try
			{
				// Send them to the collections general process
				$this->db->beginTransaction();

				// Send Return Letter 3 - 'Overdue Account Letter' 6.2.1.4
				ECash_Documents_AutoEmail::Queue_For_Send($row->application_id, 'RETURN_LETTER_3_OVERDUE_ACCOUNT');

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

				// 6.2.1.3 - Schedule Full Pull on next Payment Due Date
				// Get their next due date
				$data = Get_Transactional_Data($row->application_id);
				$info  = $data->info;
				$rules = $data->rules;

				$paydates      = Get_Date_List($info, date('m/d/Y'), $rules, 10, NULL, NULL);

				while(strtotime($paydates['event'][0]) < strtotime(date('Y-m-d')))
				{
					array_shift($paydates['event']);
					array_shift($paydates['effective']);
				}

				$next_action   = date('m/d/Y', strtotime($paydates['event'][0]));
				$next_due_date = date('m/d/Y', strtotime($paydates['effective'][0]));

				// Removed for escalation #26062
				Schedule_Full_Pull($row->application_id, NULL, NULL, $next_action, $next_due_date);

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
	
	private function getTempTableName()
	{
		return 'temp_application';
	}
	
	private function getTempTableSpec()
	{
		return array($this->getApplicationIdColumn() => 'INT UNSIGNED');
	}
	
	private function getApplicationIdColumn()
	{
		return 'application_id';
	}
}


?>
