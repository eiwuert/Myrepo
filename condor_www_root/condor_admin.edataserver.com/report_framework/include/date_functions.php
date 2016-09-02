<?php
	
	
	/**
	 *
	 * Various date-related functions, designed to be friendly to
	 * "fuzzy" intervals -- like years, months, and days -- that
	 * can have varying lengths.
	 * @author Andrew Minerd
	 *
	 */
	
	/**
	 * Calculate the difference between two dates using
	 * "fuzzy" intervals; in other words, use years, months,
	 * and days, which have different lengths throughout the
	 * year, rather than seconds, minutes, or hours, which are
	 * ALWAYS the same.
	 *
	 * @param timestamp $date1
	 * @param timestamp $date2
	 * @return array
	 */
	function Date_Diff($date1, $date2)
	{
		
		// move from largest to smallest
		$compare = array('year', 'month', 'day', 'hour', 'minute', 'second');
		
		// swap dates if necessary
		if ($date2 < $date1)
		{
			$date = $date2;
			$date2 = $date1;
			$date1 = $date;
		}
		
		// start here
		$date = $date1;
		
		foreach ($compare as $interval)
		{
			
			$count = 0;
			
			while (($next = strtotime('+1 '.$interval, $date)) <= $date2)
			{
				$date = $next;
				$count++;
			}
			
			$diff[$interval] = $count;
			
		}
		
		return $diff;
		
	}
	
	/**
	 * Add the difference calculated using Date_Diff
	 * to a date.
	 *
	 * @param timestamp $date
	 * @param array $diff
	 * @return timestamp
	 */
	function Date_Add($date, $diff)
	{
		
		foreach ($diff as $interval=>$count)
		{
			$date = strtotime('+'.$count.' '.$interval, $date);
		}
		
		return $date;
		
	}
	
	/**
	 * Subtract the difference calculated using Date_Diff
	 * to a date.
	 *
	 * @param timestamp $date
	 * @param array $diff
	 * @return timestamp
	 */
	function Date_Sub($date, $diff)
	{
		
		foreach ($diff as $interval=>$count)
		{
			$date = strtotime('-'.$count.' '.$interval, $date);
		}
		
		return $date;
		
	}
	
	/**
	 * Return a nicely formated date string (i.e., 1 day, 2 hours, 5 minutes.)
	 *
	 * @param array $diff Intervals in the format returned by Date_Diff
	 * @return string
	**/
	function Date_String($diff, $abbrev = FALSE)
	{
		
		$string = '';
		
		if (is_array($diff))
		{
			
			foreach ($diff as $interval=>$count)
			{
				
				if ($count > 0)
				{
					if ($string) $string .= ', ';
					
					if ($abbrev)
					{
						$string .= $count.$interval{0};
					}
					else
					{
						$string .= $count.' '.$interval;
						if ($count > 1) $string .= 's';
					}
					
				}
				
			}
			
			if (!$string)
			{
				$last = end(array_keys($diff));
				$string = '0'.($abbrev ? $last{0} : ' '.$last.'s');
			}
			
		}
		
		return $string;
		
	}
	
	/**
	 * Returns an array of date "variables", suitable
	 * for replacing in strings with Replace_Vars
	 *
	 * @param timestamp $time
	 * @return array
	 */
	function Date_Vars($time = NULL, $names = NULL)
	{
		
		// allow them to specify a timestamp
		if (!is_numeric($time)) $time = time();
		
		if (!is_array($names))
		{
			// default replacement variables
			$names = array('d', 'j', 'D', 'l', 'F', 'M', 'm', 'n', 'Y', 'y', 't',
				'H', 'g', 'G', 'h', 'i', 's', 'a', 'A', 'e');
		}
		// get the values for the replacement
		$values = explode("\t", date(implode("\t", $names), $time));
		
		// turn this into a key=>value array
		$vars = array_combine($names, $values);
		return $vars;
		
	}
	
	/**
	 * Replaces variables in a string, such as those
	 * returned by the Date_Vars function.
	 *
	 * @param array $vars Variables to replace
	 * @param string $string String
	 * @return string
	 */
	function Replace_Vars($vars, $string)
	{
		
		if (is_array($vars) && (strpos($string, '%') !== FALSE))
		{
			
			foreach ($vars as $name=>$value)
			{
				$string = str_replace('%'.$name, $value, $string);
			}
			
		}
		
		return $string;
		
	}
	
	function Move_Interval($date, $interval, $num = 1)
	{
		
		if (is_numeric($num) && ($num != 0))
		{
			
			$count = abs($num);
			
			$d = is_numeric($interval);
			$dir = ($num > 0);
			
			while ($count-- > 0)
			{
				if ($d) $date = ($dir ? $date + $interval : $date - $interval);
				else $date = strtotime(($dir ? '+' : '-').$interval, $date);
			}
			
		}
		
		return $date;
		
	}
	
	/**
		
		@name Neuter_Interval
		@desc Rounds a date according to a strtotime() interval.
		
		@param $date timestamp
		@param $interval string Interval accepted by strtotime (for example, "15 minutes")
		
		@return timestamp
		
	**/
	function Neuter_Interval($date, $interval)
	{
		
		$diff = (strtotime('+'.$interval, $date) - $date);
		
		if ($diff < 86400)
		{
			// get the difference
			$date = (ceil($date / $diff) * $diff);
		}
		elseif ($diff == 86400)
		{
			$date = strtotime(date('Y-m-d', $date).' +1 day', $date);
		}
		
		return $date;
		
	}
	
	/**
		
		@name Neuter_Label
		@desc Rounds a date according to a strtotime() interval and date() format.
		
		NOTE: it is imperative that you pass in a timestamp that has already been neutered
			by our sister function Neuter_Timestamp, or is based upon one that was. This
			ensures the the date actually falls upon an interval. Neglecting this warning
			may result in strange dates. And strange back-hair.
		
		@param $date timestamp
		@param $format string Format accepted by date (for example, "gA")
		@param $interval string Interval accepted by strtotime (for example, "15 minutes")
		
		@return timestamp
		
	**/
	function Neuter_Label($date, $format, $interval)
	{
		
		$s = date($format, $date);
		while ((date($format, ($next = strtotime('-'.$interval, $date))) === $s) && ($date = $next));
		
		return $date;
		
	}
	
	/**
	 *
	 * "Neuters" a date according to a strtotime() interval. The
	 * entire interval is taken into consideration when dealing with
	 * smaller displacements (minutes and seconds), so that the
	 * interval "15 minutes" is rounded to :00, :15, :30, or :45.
	 * Larger intervals (hours and above) are simply rounded to the
	 * end of the current hour, day, etc.
	 *
	 * Given the above, it is important to realize that the exact
	 * operation of this function will vary depending upon the
	 * interval that is provided: for instance, the outcome of
	 * the intervals "24 hours" and "1 day" -- while seemingly the
	 * same interval (disregarding daylight saving time) -- will
	 * be different. One will be rounded to the end of the current
	 * hour, while the other will be rounded to the end of the day.
	 *
	**/
	function Neuter_Date_By_Interval($date, $interval)
	{
		
		if (preg_match('/^(?:\+|-)?(\d+)\s+(.*?)s?$/', $interval, $matches))
		{
			
			$count = $matches[1];
			$length = strtolower($matches[2]);
			
			switch ($length)
			{
				
				// these are exact intervals, we can use
				// a mathematical rounding procedure
				case 'second':
				case 'minute':
					
					$diff = (strtotime('+'.$interval, $date) - $date);
					$date = (ceil($date / $diff) * $diff);
					break;
					
				case 'hour':
					$date = strtotime(date('Y-m-d H:00:00', $date).' +1 hour');
					break;
				
				// these are "fuzzy" intervals (their exact length varies
				// throughout the year) so we can't do this with math
				case 'day':
					$date = strtotime(date('Y-m-d', $date).' +1 day');
					break;
					
				case 'month':
					$date = strtotime(date('Y-m-1', $date).' +1 month');
					break;
					
				case 'year':
					$date = strtotime($date('Y-1-1', $date).' +1 year');
					break;
				
			}
			
		}
		
		return $date;
		
	}
	
	/**
	 *
	 * Builds an array of intervals (array($start, $end)) using $offsets. The
	 * intervals are described as (($x >= $start) && ($x < $end)).
	 *
	 */
	function Build_Intervals($offsets, $now, $base_date, $start_date, $end_date, $interval)
	{
		
		reset($offsets);
		$intervals = array();
		
		// get the real dates (resolve strtotimes, etc.)
		$end_date = Transform_Date($end_date, $now);
		$start_date = Transform_Date($start_date, $end_date);
		$base_date = Transform_Date($base_date, $end_date);
		
		/*
		echo '<pre>';
		var_dump(date('Y-m-d H:i:s', $now));
		var_dump(date('Y-m-d H:i:s', $end_date));
		var_dump(date('Y-m-d H:i:s', $start_date));
		var_dump(date('Y-m-d H:i:s', $base_date));
		
		die();
		*/
		
		while (list($key, $offset) = each($offsets))
		{
			
			$offset += $base_date;
			
			// make sure this falls within our interval (otherwise, we may end
			// up counting, say March 1st as February 29th, or something similar)
			if (($offset >= $start_date) && ($offset < $end_date))
			{
				$next = Move_Interval($offset, $interval, 1);
				$intervals[$key] = array($offset, $next);
			}
			
		}
		
		return $intervals;
		
	}
	
	/**
	 *
	 * Transforms a relative $date according to $relative_to. Date tokens
	 * are parsed according to Date_Vars and Replace_Vars.
	 *
	 */
	function Transform_Date($date, $relative_to)
	{
		
		if (!is_numeric($date))
		{
			$date = Replace_Vars(Date_Vars($relative_to), $date);
			$date = strtotime($date, $relative_to);
		}
		
		return $date;
		
	}
	
?>
