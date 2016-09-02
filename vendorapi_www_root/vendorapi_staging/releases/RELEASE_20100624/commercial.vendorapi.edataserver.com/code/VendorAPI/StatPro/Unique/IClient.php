<?php
/**
 * Interface defining methods for hitting unique stats
 * @author Adam Englander <adam.englander@sellingsource.com>
 * @author Alex Rosekrans <alex.rosekrans@partnerweekly.com>
 */
interface VendorAPI_StatPro_Unique_IClient
{
	/**
	 * Hit a unique stat
	 * @param string $stat_name
	 * @param int $application_id
	 * @param string $track_key
	 * @param string $space_key
	 */
	public function hitUniqueStat($stat_name, $application_id, $track_key = null, $space_key= null);
}