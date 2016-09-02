<?php
/* MoveReworkToSecondTier.php
 *
 * This event checks for applications that are in 'Collections Rework' status that have been
 * queued in the my queue for a business rule defined number of business days.
 *
 * This cron fulfills spec point 6.3.5.1 of the AALM Collections Specification
 * part of gForge ticket #13633.
 */

class ECash_NightlyEvent_MoveReworkToSecondTier extends ECash_Nightly_Event
{
	// Parameters used by the Cron Scheduler
	// this needs to be ran everyday, no use in setting up a business rule
	protected $business_rule_name = null; // 'move_delinquent_to_collections_rework';
	protected $timer_name = 'Move_Rework_To_Second_Tier';
	protected $process_log_name = 'move_rework_to_second_tier';
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

		$this->Move_Rework_To_Second_Tier($this->start_date, $this->end_date);
	}
	
	private function Move_Rework_To_Second_Tier($start_date, $end_date)
	{
		$holidays  = Fetch_Holiday_List();
		$pdc       = new Pay_Date_Calc_3($holidays);
		$biz_rules = new ECash_BusinessRulesCache($this->db);

		$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
		$rule_set_id  = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rules        = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

		// Maximum amount of days this app can be in the collections rework queue [6.3.2.2]
		$cdays = (isset($rules['collections']['max_days_in_rework_queue']))
			? $rules['collections']['max_days_in_rework_queue']
			: 14; 
		

		// Only collections rework
		$status_map = Fetch_Status_Map(FALSE);

		// Get the queue manager
		$qm = ECash::getFactory()->getQueueManager();
		
		$col_queue = $qm->getQueue('collections_rework');
		$col_table = $col_queue->getQueueEntryTableName();
		
		$this->createTempTableFromAppService($this->getCollectionsStatuses());

		// Get all applications in 'collections rework'
		// that have been in the queue for more than 14 days
		//[#50208] disable 'days_until_second_tier' acting on 'My Queue' for 2nd tier
		$query = "
			SELECT
				app.application_id
			FROM
				" . $this->getTempTableName() . " app
			JOIN {$col_table} nqe ON (nqe.related_id = app.application_id)
			WHERE
						DATEDIFF(NOW(), nqe.date_queued) > {$cdays}
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
				// Send them to the second tier process
				$this->db->beginTransaction();

				Update_Status(null, $row->application_id, array('pending','external_collections','*root'), NULL, NULL, FALSE);

				$this->log->Write("Moved Application: {$row->application_id} to second tier process. [6.3.5.1 #13633]");
				
				$this->db->commit();
			}
			catch (Exception $e)
			{

				$this->db->rollback();

				$this->log->Write("FAILED moving application: {$row->application_id} to second tier process.");
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
		return 'temp_moveReworkToSecondTier_application';
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
			'collections_rework::collections::customer::*root'
		);
	}
}


?>
