<?php
/* MoveGeneralToRework.php
 *
 * This event checks for applications that are in 'Collections Contact' status that have been
 * queued in the my queue for a business rule defined number of business days.
 *
 * This cron fulfills spec point 6.2.3 of the AALM Collections Specification
 * part of gForge ticket #13633.
 */

class ECash_NightlyEvent_MoveGeneralToRework extends ECash_Nightly_Event
{
	// Parameters used by the Cron Scheduler
	// this needs to be ran everyday, no use in setting up a business rule
	protected $business_rule_name = null; // 'move_delinquent_to_collections_rework';
	protected $timer_name = 'Move_General_To_Rework';
	protected $process_log_name = 'move_general_to_rework';
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

		$this->Move_General_To_Rework($this->start_date, $this->end_date);
	}
	
	private function Move_General_To_Rework($start_date, $end_date)
	{
		$holidays  = Fetch_Holiday_List();
		$pdc       = new Pay_Date_Calc_3($holidays);
		$biz_rules = new ECash_BusinessRulesCache($this->db);

		$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
		$rule_set_id  = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rules        = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

		$days = (isset($rules['collections']['days_until_rework'])) ? $rules['collections']['days_until_rework'] : 3;
		
		$end_stamp = $pdc->Get_Business_Days_Backward(date('Y-m-d'), $days);

		// Get the queue manager
		$qm = ECash::getFactory()->getQueueManager();
	
		// Get the my queue
		$agent_queue = $qm->getQueue('Agent');

		// get the my queue table
		$my_queue_table = $agent_queue->getQueueEntryTableName();

		$col_queue = $qm->getQueue('collections_general');
		$col_table = $col_queue->getQueueEntryTableName();
		
		$this->createTempTableFromAppService($this->getCollectionsStatuses());

		// Get all applications in 'collections contact' in 'My Queue'
		// that have been in the queue for more than 3 days
		// OR
		// applications in collections new status that are not in
		// 'My Queue' or the 'Collections General' queue as a failsafe
		$query = "
			SELECT
				app.application_id
			FROM
				" . $this->getTempTableName() . " app
				LEFT JOIN {$my_queue_table} mqe ON (mqe.related_id = app.application_id)
				LEFT JOIN {$col_table} nqe ON (nqe.related_id = app.application_id)
			WHERE
				mqe.date_queued <= '{$end_stamp}' OR (nqe.date_queued IS NULL AND mqe.date_queued IS NULL)
		";

		$results = $this->db->query($query);

		while ($row = $results->fetch(PDO::FETCH_OBJ))
		{
			$schedule = Fetch_Schedule($row->application_id);
			$status   = Analyze_Schedule($schedule);

			// Remove from all Queues
			$qi = new ECash_Queues_BasicQueueItem($row->application_id);
			$qm = ECash::getFactory()->getQueueManager();
			$qm->removeFromAllQueues($qi);


			try
			{
				// Send them to the collections general process
				$this->db->beginTransaction();

				Remove_Unregistered_Events_From_Schedule($row->application_id);

				// Send Return Letter 4 - 'Final Notice Letter' 6.3.1.2
				ECash_Documents_AutoEmail::Queue_For_Send($row->application_id, 'RETURN_LETTER_4_FINAL_NOTICE');

				// 6.3.1.1 - Change status to Collections Rework
				Update_Status(null, $row->application_id, array('collections_rework','collections','customer','*root'), NULL, NULL, FALSE);

				// 6.3.1.4 - Add to Collections Rework Queue
				$qm = ECash::getFactory()->getQueueManager();
				$queue_item = $qm->getQueue('collections_rework')->getNewQueueItem($row->application_id);
				$qm->moveToQueue($queue_item, 'collections_rework');

				$this->db->commit();

				$this->log->Write("Moved Application: {$row->application_id} to collections rework process. [6.2.3 #13633]");
			}
			catch (Exception $e)
			{

				$this->db->rollback();

				$this->log->Write("FAILED moving application: {$row->application_id} to collections rework process.");
				throw $e;
			}
		}

		return TRUE;
	}
	
	/**
	 * Creates a temporary table containing the application ID's returned by the application service for the specified
	 * statuses.
	 * 
	 * @param array $statuses an array of statuses
	 */
	private function createTempTableFromAppService(array $statuses)
	{
		$db = ECash::getAppSvcDB();
		$app_ids = array();
		
		foreach ($statuses as $status)
		{
			$as_query = "EXECUTE sp_fetch_application_ids_by_application_status '{$status}'";
			$result = $db->query($as_query);
			$app_ids = array_merge($app_ids, $result->fetchAll());
		}
		
		ECash_DB_Util::generateTempTableFromArray($this->db, $this->getTempTableName(), $app_ids,
				$this->getTempTableSpec(), $this->getApplicationIdColumn());
	}
	
	/**
	 * Returns the name of the temp table.
	 * 
	 * @return string
	 */
	private function getTempTableName()
	{
		return 'temp_moveGeneralToRework_application';
	}
	
	/**
	 * Returns an array that contains the spec for the temp table with the key as the column name and the value as the
	 * MySQL specification.
	 * 
	 * @return array
	 */
	private function getTempTableSpec()
	{
		return array($this->getApplicationIdColumn() => 'INT UNSIGNED');
	}
	
	/**
	 * Returns the name of the application ID column.
	 * 
	 * @return string
	 */
	private function getApplicationIdColumn()
	{
		return 'application_id';
	}
	
	/**
	 * Returns an array of collection statuses.
	 * 
	 * @return array an array of collection statuses
	 */
	private function getCollectionsStatuses()
	{
		return array(
			'queued::contact::collections::customer::*root',
			'dequeued::contact::collections::customer::*root'
		);
	}
}


?>
