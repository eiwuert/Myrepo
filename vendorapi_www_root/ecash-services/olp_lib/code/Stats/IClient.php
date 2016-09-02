<?php

/** An interface to hit a stat.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
interface Stats_IClient
{
	/** Hits a stat.
	 *
	 * @param string $event_type_key
	 * @param int $date_occurred
	 * @param int $event_amount
	 * @param string $track_key
	 * @param string $space_key
	 * @return void
	 */
	public function hitStat($event_type_key, $date_occurred = NULL, $event_amount = NULL, $track_key = NULL, $space_key = NULL);
}

?>
