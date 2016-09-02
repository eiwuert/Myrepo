<?php

require_once('event_log.singleton.class.php');

/** Stores stats that are hit into the database.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Stats_OLP_Observe_Eventlog extends Stats_OLP_Observe_Observer
{
	/** Insert this stat name into the event log.
	 *
	 * @param string $stat_name
	 * @param string $mode
	 * @param int $application_id
	 * @return void
	 */
	protected function insertEventLog($stat_name, $mode, $application_id)
	{
		$event_name = 'STAT_' . strtoupper($stat_name);
		$response_name = 'pass';
		
		$event_log = Event_Log_Singleton::Get_Instance($mode, $application_id);
		$event_log->Log_Event($event_name, $response_name);
	}
	
	/** A stat was hit through the Stats_OLP_Client system.
	 *
	 * @param Stats_OLP_Client $stats
	 * @param string $event_type_key
	 * @param int $date_occurred
	 * @param int $event_amount
	 * @param string $track_key
	 * @param string $space_key
	 * @return void
	 */
	public function statHit(Stats_OLP_Client $stats, $event_type_key, $date_occurred = NULL, $event_amount = NULL, $track_key = NULL, $space_key = NULL)
	{
		$mode = $stats->getMode();
		$application_id = $stats->getApplicationID();
		
		if ($application_id)
		{
			$this->insertEventLog($event_type_key, $mode, $application_id);
		}
	}
}

?>
