<?php
/**
 * Interface for event history providers
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 * @author Alex Rosekrans <alex.rosekrans@partnerweekly.com>
 */
interface VendorAPI_StatPro_Unique_IHistory
{
	/**
	 * Add the application/event combination to the history
	 * @param string $stat_name
	 * @param integer $application_id
	 */
	public function addEvent($stat_name, $application_id);
	
	/**
	 * Does the application/event combination exist in the history
	 * @param string $stat_name
	 * @param integer $application_id
	 * @return boolean
	 */
	public function containsEvent($stat_name, $application_id);
}