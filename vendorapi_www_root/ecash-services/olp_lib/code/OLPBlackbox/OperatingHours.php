<?php
/**
 * This class will determine if a date/time is valid based on the data added to the class.
 * The data can be added explicitly through method calls or implicitly via array import
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPBlackbox_OperatingHours
{
	const MON = 'Mon';
	const TUE = 'Tue';
	const WED = 'Wed';
	const THU = 'Thu';
	const FRI = 'Fri';
	const SAT = 'Sat';
	const SUN = 'Sun';
	
/**
	 * Array with operating hours based on day ranges
	 *
	 * @var array
	 */
	protected $day_hours = array();
	
	/**
	 * Array with operating hours based on date ranges
	 *
	 * @var array
	 */
	protected $date_hours = array();

	/**
	 * Array of valid day abbreviations in sort order
	 *
	 * @var array
	 */
	protected static $valid_day_abbreviations = array(
		self::MON,
		self::TUE,
		self::WED,
		self::THU,
		self::FRI,
		self::SAT,
		self::SUN
	);

	/**
	 * Does the provided  date/time fall within a valid start and end time for its day and date
	 *
	 * @param string $datetime A strtotime compatible date/time string
	 * @return bool
	 */
	public function isOpen($datetime)
	{
		$open = FALSE;
		$check_datetime = strtotime($datetime);

		foreach ($this->getHours($datetime) as $hour_item)
		{
			// Convert start and end time to unix timestamps
			$start = strtotime($hour_item['start'], $check_datetime);
			$end = strtotime($hour_item['end'], $check_datetime);

			//  Closed Override: If a time is set with the same start and end, this is a special case that
			// will override any other hours entries and return FALSE 
			if ($start == $end)
			{
				return FALSE;
			}
			elseif (($check_datetime > $start) && ($check_datetime < $end))
			{
				$open = TRUE;
				// Even though we have found a valid entry, we can't break the loop
				// as one of the remaining entries may have a closed override value
			}
		}
		return $open;
	}
	
	/**
	 * Adds operating hours for a specific day of the week range.
	 * 
	 * @param string $day_of_week_start Abbreviation of the starting day of a range of operating hours.
	 *   A member of the getValidDays() list.
	 * @param string $day_of_week_end Abbreviation of the ending day of a range of operating hours.
	 *   A member of the getValidDays() list.
	 * @param string $time_start strtotime compatible string with the start time for the operating hours.
	 * @param string $time_end strtotime compatible string with the end time for the operating hours.
	 * @return void
	 */
	public function addDayOfWeekHours($day_start, $day_end, $time_start, $time_end)
	{
		if (!$this->dayIsValid($day_start))
		{
			$msg = "$day_start is not a valid day value.  It must be one of the following: ".implode(', ', $this->getValidDays());
			throw new InvalidArgumentException($msg);
		}
		if (!$this->dayIsValid($day_end))
		{
			$msg = "$day_end is not a valid day value.  It must be one of the following: ".implode(', ', $this->getValidDays());
			throw new InvalidArgumentException($msg);
		}
		if (!$this->timesAreValid($time_start, $time_end))
		{
			throw new InvalidArgumentException("Invalid times supplied for $day_start to $day_end: start: $time_start : end: $time_end");
		}
		
		$normalized_time_start = $this->normalizeTime($time_start);
		$normalized_time_end = $this->normalizeTime($time_end);
		$this->day_hours[] = array(
			'day' =>  array('start'=>$day_start, 'end'=>$day_end),
			'time' => array('start'=>$normalized_time_start, 'end'=>$normalized_time_end)
		);
	}
	
	/**
	 * Adds operating hours for a specific date.
	 * 
	 * @param string $date_start strtotime compatible string with the start date for the operating hours.
	 * @param string $date_end strtotime compatible string with the end date for the operating hours.
	 * @param string $time_start strtotime compatible string with the start time for the operating hours.
	 * @param string $time_end strtotime compatible string with the end time for the operating hours.
	 * @return void
	 */
	public function addDateHours($date_start, $date_end, $time_start, $time_end)
	{
		if (!$this->timesAreValid($time_start, $time_end) || !$this->datesAreValid($date_start, $date_end))
		{
			throw new InvalidArgumentException("Invalid dates/times supplied for $date_start to $date_end: start: $time_start to end: $time_end");
		}
		$normalized_date_start = $this->normalizeDate($date_start);
		$normalized_time_start = $this->normalizeTime($time_start);
		$normalized_date_end = $this->normalizeDate($date_end);
		$normalized_time_end = $this->normalizeTime($time_end);
		$this->date_hours[] = array(
			'date' =>  array('start' => $normalized_date_start, 'end' => $normalized_date_end),
			'time' => array('start' => $normalized_time_start, 'end' => $normalized_time_end)
		);
	}
	
	/**
	 * Gets the operating hours for a specific date based on its day of the week.
	 * 
	 * @param string $date The date you want the operating hours for
	 * @return array
	 */
	protected function getDayHours($date)
	{
		$date_ts = strtotime($date);
		if ($date_ts === FALSE) throw new InvalidArgumentException("$date is not a valid date");
		$day_name = Date('D', $date_ts);
		
		// Initialize return variable
		$hours = array();

		foreach ($this->getAllDayOfWeekHours() as $day)
		{
			
			if ($this->isDayBetweenDays($day_name, $day['day']['start'], $day['day']['end']))
			{
				$hours[] = $day['time'];
			}
		}
		return $hours;
	}
	
	/**
	 * Gets the operating hours for a specific date.
	 * 
	 * @param string $date The date you want the operating hours for
	 * @return array
	 */
	protected function getDateHours($date)
	{
		$date_ts = strtotime(date('Y-m-d', strtotime($date)));
		if ($date_ts === FALSE) throw new InvalidArgumentException("$date is not a valid date");
		
		$hours = array();
		foreach ($this->getAllDateHours() as $date_item)
		{
			if (strtotime($date_item['date']['start']) <= $date_ts && strtotime($date_item['date']['end']) >= $date_ts)
			{
				$hours[] = $date_item['time'];
			}
		}
		
		return $hours;
	}
	
	/**
	 * Gets the operating ours for a specific date
	 * If there are hours specified for that date, they will be returned; 
	 * otherwise, the day's hours will be returned
	 *
	 * @param string $date A PHP strtotime compatible date string 
	 * @return array
	 */
	protected function getHours($date)
	{
		$hours = $this->getDateHours($date);
		if (empty($hours))
		{
			$hours = $this->getDayHours($date);
		}
		return $hours;
	}
	
	/**
	 * Return a representation of this object as an array
	 *
	 * @return array
	 */
	public function toArray()
	{
		$return = array();
		$return['dates'] = $this->date_hours;
		$return['days'] = $this->day_hours;
		return $return;
	}
	
	/**
	 * Populate the object from an array 
	 *
	 * @param array $input
	 * @return void
	 */
	public function fromArray(Array $input)
	{
		// If the legacy values are present, import using the old process
		if (isset($input['date']) || isset($input['day_of_week']))
		{
			$this->fromOldArray($input);
		}
		// Otherwise process normally
		else
		{
			// If a date value was supplied in the array, process it
			if (!empty($input['dates']))
			{
				// date section must be an array
				if (!is_array($input['dates']))
				{
					throw new InvalidArgumentException('The dates value must be an array');
				}
				// Cycle through dates
				foreach ($input['dates'] as $date)
				{
					// Dates and times must be set
					if (
						empty($date['date']) || empty($date['date']['start']) || empty($date['date']['end'])
						|| empty($date['time']) || empty($date['time']['start']) || empty($date['time']['end'])
					)
					{
						throw new InvalidArgumentException("Invalid date hours array: " . print_r($date, TRUE));
					}
					// Add the values
					$this->addDateHours($date['date']['start'], $date['date']['end'], $date['time']['start'], $date['time']['end']);
				}
			}
	
			// If a day_of_week value was supplied in the array, process it
			if (!empty($input['days']))
			{
				// day_of_week section must be an array
				if (!is_array($input['days']))
				{
					throw new InvalidArgumentException('The days value must be an array');
				}
				// Cycle through days
				foreach ($input['days'] as $day)
				{
					// Times must be set
					if (
						empty($day['day']) || empty($day['day']['start']) || empty($day['day']['end'])
						|| empty($day['time']) || empty($day['time']['start']) || empty($day['time']['end'])
					)
					{
						throw new InvalidArgumentException("Invalid day hours array: " . print_r($day, TRUE));
					}
					// Add the values
					$this->addDayOfWeekHours($day['day']['start'], $day['day']['end'], $day['time']['start'], $day['time']['end']);
				}
			}
		}
	}

	/**
	 * Populate the object from an array that used the old single day/date format
	 *
	 * @param array $input
	 * @return void
	 * @todo Remove this function once the old data values are converted
	 */
	protected function fromOldArray(Array $input)
	{
		// If a date value was supplied in the array, process it
		if (!empty($input['date']))
		{
			// date section must be an array
			if (!is_array($input['date']))
			{
				throw new InvalidArgumentException('The date value must be an array');
			}
			// Cycle through dates
			foreach ($input['date'] as $date => $date_array)
			{
				// Cycle through entries for the date
				foreach ($date_array as $hours)
				{
					// Times must be set
					if (empty($hours['start']) || empty($hours['end']))
					{
						throw new InvalidArgumentException("Invalid date hours array for $date");
					}
					// Add the values
					$this->addDateHours($date, $date, $hours['start'], $hours['end']);
				}
			}
		}

		// If a day_of_week value was supplied in the array, process it
		if (!empty($input['day_of_week']))
		{
			// day_of_week section must be an array
			if (!is_array($input['day_of_week']))
			{
				throw new InvalidArgumentException('The day_of_week value must be an array');
			}
			// Cycle through days
			foreach ($input['day_of_week'] as $day => $day_array)
			{
				// Cycle through entries for the day
				foreach ($day_array as $hours)
				{
					// Times must be set
					if (empty($hours['start']) || empty($hours['end']))
					{
						throw new InvalidArgumentException("Invalid day hours array for $day");
					}
					
					// Convert old day/range identifier into a new day range
					if ($day == 'WkDays')
					{
						$day_start = self::MON;
						$day_end = self::FRI;
					}
					elseif ($day == 'WkEnd')
					{
						$day_start = self::SAT;
						$day_end = self::SUN;
						
					}
					elseif ($day == 'WkAll')
					{
						$day_start = self::MON;
						$day_end = self::SUN;
					}
					else
					{
						$day_start = $day_end = $day;
					}
					// Add the values
					$this->addDayOfWeekHours($day_start, $day_end, $hours['start'], $hours['end']);
				}
			}
		}
	}

	/**
	 * Is the day value valid
	 *
	 * @param string $day
	 * @return bool
	 */
	public static function dayIsValid($day)
	{
		return in_array($day, self::getValidDays());
	}
	
	/**
	 * Is the time value valid
	 *
	 * @param string $time
	 * @return bool
	 */
	public static function timeIsValid($time)
	{
		$format_valid = (bool)preg_match('/(?<hour>\d{2}):(?<minute>\d{2})/', $time, $matches);
		
		$valid = $format_valid
			&& !empty($time)
			&& (int)$matches['hour'] >= 0
			&& (int)$matches['hour'] <= 23
			&& (int)$matches['minute'] >= 0
			&& (int)$matches['minute'] <= 59;
		return $valid;
	}

	/**
	 * Are the start and end times valid
	 *
	 * @param string $start
	 * @param string $end
	 * @return bool
	 */
	public static function timesAreValid($start, $end)
	{
		if (self::timeIsValid($start) && self::timeIsValid($end)
			&& strtotime($start) <= strtotime($end))
		{
			$valid = TRUE;
		}
		else
		{
			$valid = FALSE;
		}
		return $valid;
	}
	
	/**
	 * Normalize the date string between functions
	 *
	 * @param string $date
	 * @return string
	 */
	protected static function normalizeDate($date)
	{
		if (FALSE === $date_val = strtotime($date))
		{
			throw new InvalidArgumentException("$date is not a valid date string");
		}
		$date_string = Date('Y-m-d', $date_val);
		return $date_string;
	}

	/**
	 * Normalize the time string between functions
	 *
	 * @param string $time
	 * @return string
	 */
	protected static function normalizeTime($time)
	{
		if (FALSE === $time_val = strtotime($time))
		{
			throw new InvalidArgumentException("$time is not a valid time string");
		}
		$time_string = Date('H:i', $time_val);
		return $time_string;
	}

	/**
	 * Get all date based operating hours entries
	 *
	 * @return array
	 */
	public function getAllDateHours()
	{
		return $this->date_hours;
	}
		
	/**
	 * Get all day of week based operating hours entries
	 *
	 * @return array
	 */
	public function getAllDayOfWeekHours()
	{
		return $this->day_hours;
	}

	/**
	 * Check to see if the date string provided is valid
	 * 
	 * @param string $date
	 * @return bool
	 */
	public static function dateIsValid($date)
	{
		return !(strtotime($date) === FALSE);
	}

	/**
	 * Are the start and end dates valid
	 *
	 * @param string $start
	 * @param string $end
	 * @return bool
	 */
	public static function datesAreValid($start, $end)
	{
		return (self::dateIsValid($start) && self::dateIsValid($end) && strtotime($end) >= strtotime($start));
	}

	/**
	 * Is a day in the range defined by two days
	 *
	 * @param string $day_check Day to check
	 * @param string $day_start Starting day of range
	 * @param string $day_end Ending day of range
	 * @return bool
	 */
	public static function isDayBetweenDays($day_check, $day_start, $day_end)
	{
		$start_key = array_search($day_start, self::getValidDays());
		$end_key = array_search($day_end, self::getValidDays());
		$day_key = array_search($day_check, self::getValidDays());

		$ranges = array();
		if ($end_key < $start_key)
		{
			$ranges[] = array('start' => 0, 'end' => $end_key);
			$ranges[] = array('start' => $start_key, 'end' => 6);
		}
		else
		{
			$ranges[] = array('start' => $start_key, 'end' => $end_key);
		}

		// Cycle through the ranges and return true if the day falls in one of the ranges
		foreach ($ranges as $range)
		{
			if ($day_key >= $range['start'] && $day_key <= $range['end']) return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Get array of valid days
	 *
	 * @return array
	 */
	public static function getValidDays()
	{
		return self::$valid_day_abbreviations;
	}
}

?>