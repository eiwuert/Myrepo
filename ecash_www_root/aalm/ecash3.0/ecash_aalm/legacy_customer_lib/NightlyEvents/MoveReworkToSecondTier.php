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

		$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'company_level');
		$rule_set_id  = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rules        = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

		// Maximum amount of days this app can be in the collections rework queue [6.3.2.2]
		$days = (isset($rules['collections_processes']['days_until_second_tier'])) ? $rules['collections_processes']['days_until_second_tier'] : 30;

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
				AND asf_app.level0 = 'collections_rework' AND asf.level1 = 'collections'
				AND asf.level0 = 'collections_rework' AND asf.level1 = 'collections'
				AND sh2.status_history_id IS NULL
				GROUP BY app.application_id
				HAVING total_time > {$days}
				ORDER BY sh.status_history_id
		";
		
		$results = $this->db->query($query);

		while ($row = $results->fetch(PDO::FETCH_OBJ))
		{
			//$schedule = Fetch_Schedule($row->application_id);
			//$status   = Analyze_Schedule($schedule);

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
