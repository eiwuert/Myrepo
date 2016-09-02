<?php
/**
 * StatPro client for maintaining application uniqueness of stats
 * via the hitUniqueStat method
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 * @author Alex Rosekrans <alex.rosekrans@partnerweekly.com>
 */
class VendorAPI_StatPro_Unique_Client extends VendorAPI_StatProClient
		implements VendorAPI_StatPro_Unique_IClient
{
	/**
	 * Array of history providers
	 * @var array
	 */
	protected $histories = array();

	/**
	 * Add a history provider to the provider collection
	 * @param VendorAPI_StatPro_Unique_IHistory $history
	 * @return unknown_type
	 */
	public function addHistory(VendorAPI_StatPro_Unique_IHistory $history)
	{
		$this->histories[] = $history;
	}

	/**
	 * @see code/VendorAPI/StatPro/Unique/VendorAPI_StatPro_Unique_IClient#hitUniqueStat($stat_name, $track_key, $space_key)
	 */
	public function hitUniqueStat($stat_name, $application_id, $track_key = null, $space_key= null)
	{
		if (!$this->alreadyHit($stat_name, $application_id))
		{
			$this->hitStat($stat_name, $track_key, $space_key);
			$this->recordStatHit($stat_name, $application_id);
		}
	}

	/**
	 * @param string $stat_name
	 * @param integer $application_id
	 */
	protected function recordStatHit($stat_name, $application_id)
	{
		foreach ($this->histories as $history)
		{
			$history->addEvent($stat_name, $application_id);
		}
	}

	/**
	 * @param string $stat_name
	 * @param integer $application_id
	 * @return bool
	 */
	protected function alreadyHit($stat_name, $application_id)
	{
		$hit = FALSE;
		foreach ($this->histories as $history)
		{
			if ($history->containsEvent($stat_name, $application_id))
			{
				$hit = TRUE;
				break;
			}
		}
		return $hit;
	}
}
?>