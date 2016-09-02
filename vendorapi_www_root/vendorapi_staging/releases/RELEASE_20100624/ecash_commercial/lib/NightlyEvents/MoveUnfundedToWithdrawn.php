<?php

	/**
	 * [#54167] This script will move accounts to the 'Withdrawn' status where:
	 * - They have no schedule (have not been funded)
	 * - They aren't in an agent queue, or have been for over 14 days
	 * - They've been in the same status for over 14 days
	 * - They're not in denied, withdrawn, disagree, declined, or confirm declined
	 * 
	 */
class ECash_NightlyEvent_MoveUnfundedToWithdrawn extends ECash_Nightly_Event
{
	// Parameters used by the Cron Scheduler
	protected $business_rule_name = 'move_unfunded_to_withdrawn';
	protected $timer_name = 'Move_Unfunded_To_Withdrawn';
	protected $process_log_name = 'move_unfunded_to_withdrawn';
	protected $use_transaction = FALSE;

	const DAYS_OLD = 7;

	public function __construct()
	{
		$this->classname = __CLASS__;

		parent::__construct();
	}
	
	public function run()
	{
		// Sets up the Applog, any other pre-requisites in the parent
		parent::run();

		$this->Move_Unfunded_To_Withdrawn($this->server, $this->start_date);
	}

	/**
	 * @param Server $server
	 * @param string $run_date
	 */
	function Move_Unfunded_To_Withdrawn(Server $server, $run_date)
	{
		$db = ECash::getMasterDb();
		$log = $server->log;
		$factory = ECash::getFactory();

		// We're looking for accounts in our application status set (collections tree) that have had more than 120 days since first entering collections [#48876]
		// and have no scheduled entries at all
		$sql = "SELECT a.application_id
				FROM application a
				LEFT JOIN event_schedule es on (es.application_id = a.application_id)
				LEFT JOIN n_agent_queue_entry q on (q.related_id = a.application_id)
				WHERE es.application_id is NULL
				AND a.date_application_status_set < DATE_SUB(?, INTERVAL ".self::DAYS_OLD." DAY)
				AND (q.related_id IS NULL or q.date_available < DATE_SUB(?, INTERVAL ".self::DAYS_OLD." DAY))
				";
		// querying against date_application_status_set is a no-no in the chasm world, but that data is not included
		// in the stored proceedure we're going to use

		$app_ids = $db->querySingleColumn($sql, array($run_date, $run_date));
		$app_data = $factory->getData('Application');
		$app_data = $app_data->getApplicationData($app_ids);
		$unfunded_statuses = $this->getUnfundedStatuses();
		
		foreach($app_data as $app)
		{
			if(!in_array($app['application_status'], $unfunded_statuses))
			{
				$log->Write("[App: {$app['application_id']}] Moving account from {$app['application_status']} to Withdrawn");

				Update_Status(null, $app['application_id'], array('withdrawn','applicant','*root'));
			}
		}
	}

	/**
	 * Returns an array of unfunded statuses.
	 * 
	 * @return array
	 */
	private function getUnfundedStatuses()
	{
		return array(
			'denied::applicant::*root',
			'withdrawn::applicant::*root',
			'confirm_declined::prospect::*root',
			'declined::prospect::*root',
			'disagree::prospect::*root',
			);
	}		
}

?>
