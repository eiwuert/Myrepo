<?php
/* MoveDelinquentToCollectionsRework.php
 *
 * This event checks for applications that are in 'Collections New' status that had a fatal
 * return on their last transaction, and moves them to the collections rework process
 *
 * This cron fulfills spec point 6.1.5 of the AALM Collections Specification
 * part of gForge ticket #13633 Upload dated '2008-12-22 10:50:54'
 */

class ECash_NightlyEvent_MoveDelinquentToCollectionsRework extends ECash_Nightly_Event
{
	// Parameters used by the Cron Scheduler
	// this needs to be ran everyday, no use in setting up a business rule
	protected $business_rule_name = null; // 'move_delinquent_to_collections_rework';
	protected $timer_name = 'Move_Delinquent_To_Collections_Rework';
	protected $process_log_name = 'move_delinquent_to_collections_rework';
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

		$this->Move_Delinquent_To_Collections_Rework($this->start_date, $this->end_date);
	}
	
	private function Move_Delinquent_To_Collections_Rework($start_date, $end_date)
	{
		$holidays  = Fetch_Holiday_List();
		$pdc       = new Pay_Date_Calc_3($holidays);
		$biz_rules = new ECash_BusinessRulesCache($this->db);

		$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
		$rule_set_id  = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rules        = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

		// The number of calendar days that must pass until fatal returns of collections new apps go to Collections Rework Process
		$days = (isset($rules['collections']['days_until_fatal_rework'])) ? $rules['collections']['days_until_fatal_rework'] : 15;
		
		$this->createTempTableFromAppService($this->getCollectionsStatuses());

		$query = "
			SELECT
				app.application_id
			FROM
				" . $this->getTempTableName() . " app
			WHERE
				/* Check their last transaction for fatal returns */
				(
					SELECT
						iarc.is_fatal
					FROM
						ach iach
					JOIN
						ach_return_code iarc ON (iarc.ach_return_code_id = iach.ach_return_code_id)
					WHERE
						iach.application_id = app.application_id
					AND
						DATEDIFF(NOW(), iach.date_modified) > {$days}
					ORDER BY iach.ach_id DESC
					LIMIT 1
				) = 'yes'
		";

		$results = $this->db->query($query);

		while ($row = $results->fetch(PDO::FETCH_OBJ))
		{
			$schedule = Fetch_Schedule($row->application_id);
			$status   = Analyze_Schedule($schedule);

			// Do not send them if they have arrangements
			if ($status->has_arrangements)
				continue;
			try
			{
				// Send them to the collections rework process
				$this->db->beginTransaction();

				// 6.3.1.3
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

				$this->log->Write("Moved Application: {$row->application_id} to collections rework process. [6.1.5 #13633]");
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
		return 'temp_moveDelinquentToCollectionsRework_application';
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
			'new::collections::customer::*root'
		);
	}
}


?>
