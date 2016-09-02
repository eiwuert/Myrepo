<?php

/** Stores stats that are hit into the database.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
abstract class Stats_OLP_Observe_Observer
{
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
	abstract public function statHit(Stats_OLP_Client $stats, $event_type_key, $date_occurred = NULL, $event_amount = NULL, $track_key = NULL, $space_key = NULL);
	
	/** Attach this object to Stats_OLP_Client.
	 *
	 * @param Stats_OLP_Client $stats
	 * @return void
	 */
	public function observeHitStat(Stats_OLP_Client $stats)
	{
		$d = Delegate_1::fromMethod($this, 'statHit');
		$stats->attachObserver($d);
	}
}

?>
