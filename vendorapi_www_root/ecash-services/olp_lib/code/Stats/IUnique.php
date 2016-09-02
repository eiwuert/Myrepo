<?php

/** Interface to retrieve unique stats.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
interface Stats_IUnique
{
	/** Returns an array of unique stats already hit.
	 *
	 * @return array
	 */
	public function getUniqueStatHistory();
	
	/** Determine if this specific stat has been hit yet.
	 *
	 * @param string $stat_name
	 * @return bool
	 */
	public function wasStatHitYet($stat_name);
	
	/** Returns an array of unique stats hit during this load so far.
	 *
	 * @return array
	 */
	public function getNewUniqueStatHistory();
}

?>
