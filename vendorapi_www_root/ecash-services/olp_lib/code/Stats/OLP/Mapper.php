<?php

/** This adds the ability to rename stats.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
abstract class Stats_OLP_Mapper extends Stats_OLP_UniqueSession
{
	/** An array of stats to be mapped to a different stat name.
	 * matched => stat hit
	 *
	 * @var array
	 */
	protected $map_stat_names = array(
		'visitor' => 'visitors',
		'prequal' => 'base',
		'submit' => 'income',
		'agree' => 'accepted',
		'nms_prequal' => 'post',
		'popagree' => 'legal',
	);
	
	/** Maps a stat to a different name.
	 *
	 * @param string $event_type_key
	 * @param int $date_occurred
	 * @param int $event_amount
	 * @param string $track_key
	 * @param string $space_key
	 * @return bool
	 */
	public function hitStat($event_type_key, $date_occurred = NULL, $event_amount = NULL, $track_key = NULL, $space_key = NULL)
	{
		$event_type_key = $this->mapStatName($event_type_key);
		
		return parent::hitStat($event_type_key, $date_occurred, $event_amount, $track_key, $space_key);
	}
	
	/** Returns an array of stat names, both old and new ones.
	 *
	 * @param mixed $stat_names
	 * @return array
	 */
	public function findMappedStats($stat_names)
	{
		$result = array();
		
		// For to an array and lowercase everything
		if (!is_array($stat_names))
		{
			$stat_names = array($stat_names);
		}
		$stat_names = array_map('strtolower', $stat_names);
		
		// Add all stat names (both old and new) to result
		foreach ($stat_names AS $stat_name)
		{
			$result[] = $stat_name;
			$result[] = $this->mapStatName($stat_name);
			$result[] = $this->reverseMapStatName($stat_name);
		}
		
		// Strip out any duplicates
		$result = array_unique($result);
		
		return $result;
	}
	
	/** Returns the mapped stat name. If there is no mapping, returns original
	 * stat name.
	 *
	 * @param string $event_type_key
	 * @return string
	 */
	protected function mapStatName($event_type_key)
	{
		if (isset($this->map_stat_names[$event_type_key]))
		{
			$event_type_key = $this->map_stat_names[$event_type_key];
		}
		
		return $event_type_key;
	}
	
	/** Returns the old stat name. If there is no mapping, returns original
	 * stat name.
	 *
	 * @param string $event_type_key
	 * @return string
	 */
	protected function reverseMapStatName($event_type_key)
	{
		$key = array_search($event_type_key, $this->map_stat_names);
		
		if ($key !== FALSE)
		{
			$event_type_key = $key;
		}
		
		return $event_type_key;
	}
}

?>
