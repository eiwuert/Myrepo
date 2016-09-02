<?php

class ECash_Monitoring_Performance extends ECash_Monitoring_RequestLog
{
	private $log_db;
	private $log_array;
	private $log_data_array;
	private $results = array();
	private $track_items = NULL;
	private $ticks;

	private $start_timestamp;
	private $end_timestamp;
	private $increment_amount;
	private $time_format;
	private $time_group_stamps = array();


	// An array of items we want results for
	public function setTrackItems($track_items)
	{
		if( is_array($track_items) )
		{
			$this->track_items = $track_items;
		}
	}

	// Parse desired data and store in the results array
	// Parse-Stats -> getStats
	public function getStats($start_timestamp, $end_timestamp, $increment_amount, $time_format = FALSE, $agent_id = null)
	{
		$company_map = array();
		$this->start_timestamp = $start_timestamp;
		$this->end_timestamp = $end_timestamp;
		$this->increment_amount = $increment_amount;
		$this->time_format = $time_format;
		$this->results = array();

		try
		{
			if (!$requests = self::getRequests($this->start_timestamp, $this->end_timestamp, $agent_id))
			{
				ECash::getLog()->write("Request Log Error: ". self::last_error);
				return false;
			}
		}
		catch (Exception $e)
		{
			ECash::GetLog()->write("Request Log Error: {$e->getMessage()}");
			return false;
		}

		foreach ($requests as $request)
		{
			$stampy = $request['start_time'];
			$company = $request['company_id'];
			$company_map = Fetch_Company_Map();

			foreach ($this->track_items as $item => $label)
			{
				if (!isset($agent_id))
				{
					$index = $company_map[$company];
				} else

				{
					$index = $request['agent_id'];
				}

				if (!isset($this->results[$label][$index]))
				{
					$this->initCompany($this->results[$label][$index]);
				}
				if($time_format !== FALSE)
				{
					$time_group = $this->Get_Time_Group($stampy);

					if( $time_group !== FALSE)
					{
						$this->results[$label][$index][$time_group][] = $request[$item];
					}
				}
				else
				{
					$this->results[$label][$index][] = $request[$item];
				}
			}
		}

		foreach($this->results as $item_tracked => $companies)
		{
			foreach($companies as $company => $time_group)
			{
				while( $key = key($time_group) )
				{
					if( !count($this->results[$item_tracked][$company][$key]) )
					{
						$this->results[$item_tracked][$company][$key][] = 0;
					}

					next($time_group);
				}
			}
		}

		ksort($this->results);

		return $this->results;
	}

	// Return the time group the timestamp falls in
	public function getTicks()
	{
		if( !count($this->ticks) )
		{
			$ticks = array();

			$x = strtotime("+".$this->increment_amount, $this->start_timestamp);

			while($x <= $this->end_timestamp)
			{
				$ticks[] = date($this->time_format, $x);
				$x = strtotime("+".$this->increment_amount, $x);
			}

			$this->ticks = $ticks;
		}
		return $this->ticks;
	}

	// Return the time group the timestamp falls in
	private function getTimeGroup($timestamp)
	{
		$current = $this->start_timestamp;

		while($current <= $this->end_timestamp)
		{
			if( $current > $timestamp )
			{
				return date( $this->time_format, $current);
			}

			$current = strtotime("+".$this->increment_amount, $current);
		}

		return FALSE;
	}

	private function initCompany(&$arr) {
		$current = $this->start_timestamp;
		$current = strtotime("+".$this->increment_amount, $current);

		while ($current <= $this->end_timestamp)
		{
			$arr[date( $this->time_format, $current)] = array();
			$current = strtotime("+".$this->increment_amount, $current);
		}
	}

	// Find the first line with a date in the log, can start from the end of the file or the beginning.
	private function getApplogDate($data, $direction = "forward")
	{
		$timestamp = -1;

		if($direction == "forward")
		{
			for($i = 0; $i < count($data); $i++)
			{
				if( strlen($data[$i]) >= 19 )
				{
					$timestamp = $this->getTimestamp($data[$i]);

					if($timestamp != -1)
						break;
				}
			}
		}
		elseif ($direction == "backward")
		{
			for($i = count($data)-1; $i > 0; $i--)
			{
				if( strlen($data[$i]) >= 19 )
				{
					$timestamp = $this->getTimestamp($data[$i]);

					if($timestamp != -1)
						break;
				}
			}
		}
		return $timestamp;
	}

	private function getLogList($start_from = NULL)
	{
		if(NULL === $start_from)
		{
			$this->log_array = array();

			foreach($this->log_location_list as $log_location)
			{
				$this->getLogList($log_location);
			}

			sort($this->log_array);

			return(NULL);
		}

		if( is_dir($start_from) )
		{
			$handle = opendir($start_from);

			if(is_resource($handle))
			{
				while( FALSE !== ( $file = readdir($handle) ) )
				{
					if("." != $file && ".." != $file)
					{
						$this->getLogList("$start_from/$file");
					}
				}

				closedir($handle);
			}

			return(NULL);
		}

		if( is_file($start_from) && ( "/current" == strrchr($start_from,"/") || preg_match("/log\.\d+\.gz/", $start_from) ) )
		{
			$this->log_array[] = $start_from;
			return(NULL);
		}
	}
	
	// Get the timestamp from an applog line
	private function getTimestamp($applog_line)
	{
		return strtotime( str_replace(".", "-", substr($applog_line, 0, 19) ) );
	}   	
}
?>