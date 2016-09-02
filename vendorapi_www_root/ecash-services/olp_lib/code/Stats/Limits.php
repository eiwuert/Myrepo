<?php

/**
 * Record and retrieve stat limits.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class Stats_Limits
{
	const DATE_FORMAT = 'Y-m-d';
	
	/**
	 * @var DB_IConnection_1
	 */
	protected $db;
	
	/**
	 * Initialize the class.
	 */
	public function __construct(DB_IConnection_1 $db)
	{
		$this->db = $db;
	}
	
	/**
	 * Is this stat over the limit?
	 *
	 * @param string $stat_name
	 * @param SiteConfig $config
	 * @return bool
	 */
	public function overLimit($stat_name, SiteConfig $config)
	{
		// Over limit defaults to FALSE
		$over_limit = FALSE;
		
		// Only process if the config contains stat_caps
		if (isset($config->limits->stat_caps))
		{
			$type_cap_stats = $this->normalizeStatCaps($config->limits->stat_caps);
			foreach ($type_cap_stats as $type => $cap_stats)
			{
				$stats = array_keys($cap_stats);
				
				// If the stat we are looking for is not in these stats, skip to the next iteration
				if (!in_array($stat_name, $stats)) continue;
				foreach ($type_cap_stats[$type][$stat_name] as $cap_type => $cap) 
				{
					switch (strtoupper($cap_type)) 
					{
						case 'DAILY':
							$start_date = NULL;
							$end_date = NULL;
							break;
						case 'WEEKLY':
							// if it's not sunday, use last sunday to 
							// start from otherwise we just start today
							if (date('N', $this->getNow()) != 7)
							{
								$start_date = "last sunday";
							}
							else 
							{
								$start_date = "today";
							}
							$end_date = "today";
							break;
					}
					switch (strtolower($type))
					{
						case 'site_id':
							$current = $this->count($stat_name, $config->site_id, NULL, NULL, $start_date, $end_date);
							break;
							
						case 'promo_id':
							$current = $this->count($stat_name, NULL, $config->promo_id, NULL, $start_date, $end_date);
							break;
							
						case 'vendor_id':
							$current = $this->count($stat_name, NULL, NULL, $config->vendor_id, $start_date, $end_date);
							break;
						
						default:
							$current = FALSE;
							break;
					}
					if ($current !== FALSE && $current >= (int)$cap) 
					{
						$over_limit = TRUE;
						break;
					}
				}
			}
		}

		return $over_limit;
	}
	
	/**
	 * Format the old caps to the new format
	 * @param array $stat_caps
	 * @return array
	 */
	protected function normalizeStatCaps($stat_caps) 
	{
		foreach ($stat_caps as $type => $stats) 
		{
			foreach ($stats as $stat => $cap_info) 
			{
				if (is_numeric($cap_info))
				{
					$stat_caps[$type][$stat] = array("DAILY" => $cap_info);
				}
			}	
		}
		return $stat_caps;
	}
	
	/**
	 * Increment this stat limit by one.
	 *
	 * @param string $stat_name
	 * @param int $site_id
	 * @param int $promo_id
	 * @param int $vendor_id
	 * @param mixed $date UNIX time format or string convertable via strtotime
	 * @return void
	 */
	public function increment($stat_name, $site_id = NULL, $promo_id = NULL, $vendor_id = NULL, $date = NULL)
	{
		if (is_string($date)) $date = strtotime($date);
		if (!is_numeric($date) || $date == -1 /* strtotime returns this before PHP 5.1.0 */) $date = time();
		
		$query = "
				INSERT INTO
					stat_limits (stat_date, stat_name, site_id, promo_id, vendor_id, count)
				VALUES
					(?, ?, ?, ?, ?, 1)
				ON DUPLICATE KEY UPDATE
					count = count + 1
			";
		
		$st = $this->db->prepare($query);
		
		$args = array(
			date(self::DATE_FORMAT, $date),
			strtolower($stat_name),
			(int)$site_id,
			(int)$promo_id,
			(int)$vendor_id,
		);
		
		$st->execute($args);
	}
	
	/**
	 * Retrieve a sum of a number of stats for the given parameters
	 * 
	 * @param array $stat_names Stat names to count
	 * @param int $site_id
	 * @param int $promo_id
	 * @param int $vendor_id
	 * @param mixed $date_start UNIX time format or string convertable via strtotime
	 * @param mixed $date_end UNIX time format or string convertable via strtotime
	 * @return int
	 */
	public function sum($stat_names, $site_id = NULL, $promo_id = NULL, $vendor_id = NULL, $date_start = NULL, $date_end = NULL)
	{
		$count = 0;
		
		$st = $this->runQuery($stat_names, $site_id, $promo_id, $vendor_id, $date_start, $date_end);
		while ($row = $st->fetch())
		{
			$count += (int)$row['count'];
		}
		
		return $count;
	}
	
	/**
	 * Retrieve how many times this stat has been hit for a specific day
	 * or date range.
	 *
	 * @param string $stat_name Stat name to count
	 * @param int $site_id
	 * @param int $promo_id
	 * @param int $vendor_id
	 * @param mixed $date_start UNIX time format or string convertable via strtotime
	 * @param mixed $date_end UNIX time format or string convertable via strtotime
	 * @return int
	 */
	public function count($stat_name, $site_id = NULL, $promo_id = NULL, $vendor_id = NULL, $date_start = NULL, $date_end = NULL)
	{
		$count = 0;
		
		$st = $this->runQuery(array($stat_name), $site_id, $promo_id, $vendor_id, $date_start, $date_end);
		if ($row = $st->fetch())
		{
			$count = (int)$row['count'];
		}
		
		return $count;
	}
	
	/**
	 * Runs a search on the stat_limits table for the specified parameters
	 * 
	 * @param array $stat_names Stat name to count
	 * @param int $site_id
	 * @param int $promo_id
	 * @param int $vendor_id
	 * @param mixed $date_start UNIX time format or string convertable via strtotime
	 * @param mixed $date_end UNIX time format or string convertable via strtotime
	 * @return PDOStatement
	 */
	protected function runQuery(array $stat_names, $site_id = NULL, $promo_id = NULL, $vendor_id = NULL, $date_start = NULL, $date_end = NULL)
	{
		$date_start = $this->getStartDate($date_start);
		$date_end = $this->getEndDate($date_end);
		
		$arg_values = array();
		for ($i = 0; $i < count($stat_names); $i++)
		{
			$arg_values["stat_name{$i}"] = $stat_names[$i];
		}
		
		$where_args = array('stat_name IN (:' . implode(',:', array_keys($arg_values)) . ')'); 
		
		
		if ($date_end)
		{
			$where_args[] = "stat_date BETWEEN :date_start AND :date_end";
			$arg_values['date_start'] = date(self::DATE_FORMAT, $date_start);
			$arg_values['date_end'] = date(self::DATE_FORMAT, $date_end);
		}
		else
		{
			$where_args[] = "stat_date = :date_start";
			$arg_values['date_start'] = date(self::DATE_FORMAT, $date_start);
		}
		
		if ($site_id !== NULL)
		{
			$where_args[] = "site_id = :site_id";
			$arg_values['site_id'] = $site_id;
		}
		
		if ($promo_id !== NULL)
		{
			$where_args[] = "promo_id = :promo_id";
			$arg_values['promo_id'] = $promo_id;
		}
		
		if ($vendor_id !== NULL)
		{
			$where_args[] = "vendor_id = :vendor_id";
			$arg_values['vendor_id'] = $vendor_id;
		}

		$where_args = implode(' AND ', $where_args);
		
		$query = "
			SELECT
				SUM(count) AS count
			FROM
				stat_limits
			WHERE
				{$where_args}
			GROUP BY stat_name";

		$st = $this->db->prepare($query);
		$st->execute($arg_values);
		
		return $st;
	}
	
	/**
	 * Return the timestamp of the start date 
	 * @param $date_start
	 * @return timestamp
	 */
	protected function getStartDate($date_start)
	{
		if (is_string($date_start)) 
		{
			$date_start = strtotime($date_start);
		}
		if (!is_numeric($date_start)) 
		{
			$date_start = $this->getNow();
		}
		return $date_start;
	}

	/**
	 * Return the timestamp of the end_date
	 * @param $date_end
	 * @return timestamp
	 */
	protected function getEndDate($date_end)
	{
		if (is_string($date_end))
		{
			$date_end = strtotime($date_end);
		}
		if (!is_numeric($date_end))
		{
			$date_end = NULL;
		}
		return $date_end;
	}
	
	
	/**
	 * Get the current timestamp
	 * This method is to allow for mocking in the unit tests
	 *
	 * @return unknown
	 */
	protected function getNow()
	{
		return time();
	}
}

?>