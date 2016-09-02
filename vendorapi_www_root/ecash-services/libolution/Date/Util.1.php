<?php
/**
 * @package Date
 */

  /**
   * @author Justin Foell <justin.foell@sellingsource.com>
   */
class Date_Util_1
{
	/**
	 * Calculates the difference between two dates.  The lesser date
	 * is always subtracted from the greater.
	 *
	 * @todo should probably be 'daysDiff'
	 * @param int|string $date1 unix timestamp or date string
	 * @param int|string $date2 unix timestamp or date string
	 * @return int number of days difference
	 */
	public static function dateDiff($date1, $date2)
	{
		if(!self::isTimestamp($date1))
			$date1 = strtotime($date1);
		if(!self::isTimestamp($date2))
			$date2 = strtotime($date2);
		   
		return abs(unixtojd($date1) - unixtojd($date2));
	}
	
	/**
	 * Determine if a variable is a timestamp
	 *
	 * @param mixed $value
	 */
	public static function isTimestamp($value)
	{
		return preg_match('/^[-]?[0-9]+$/', $value);
	}

	/**
	 * "Converts" a unix timestamp from one timezone to another.
	 *
	 * This method will add the appropriate number of seconds necessary to alter 
	 * the make date calculations on a timestamp accurate with a new timezone
	 * without having to change the timezone of your script as a whole.
	 *
	 * @param int $timestamp Unix Timestamp
	 * @param mixed $from_timezone Timezone name or DateTimeZone object
	 * @param mixed $to_timezone Timezone name or DateTimeZone object
	 * @return int Unix Timestamp
	 */
	function convertTimeZone($timestamp, $from_timezone, $to_timezone)
	{
		$date_time = new DateTime('@'.$timestamp, new DateTimeZone("GMT"));

		$from_dtz = $from_timezone instanceof DateTimeZone ? $from_timezone : new DateTimeZone($from_timezone);
		$to_dtz = $to_timezone instanceof DateTimeZone ? $to_timezone : new DateTimeZone($to_timezone);

		$from_offset = $from_dtz->getOffset($date_time);
		$to_offset = $to_dtz->getOffset($date_time);

		return $timestamp + ($to_offset - $from_offset);
	}

	/**
	 * Returns milliseconds, usually for creating a JavaScript Date object
	 *
	 * @param int seconds (usually unix timestamp)
	 * @return int milliseconds (usually time in UTC milliseconds)
	 */
	public static function secondsToMillis($seconds)
	{
		return $seconds . '000';
	}

	/**
	 * chops milliseconds (usually passed from JavaScript)
	 *
	 * @param int $milliseconds
	 * @return int seconds (usually unix timestamp)
	 */
	public static function millisToSeconds($milliseconds)
	{
		return substr($milliseconds, 0, -3);
	}
	
	/**
	 * Determines the age in years between two dates. Normalizes away the
	 * time parts of the dates.
	 *
	 * @param int $date_of_birth
	 * @param int $compare_date
	 * @return int
	 */
	public static function getAge($date_of_birth, $compare_date = NULL)
	{
		if ($compare_date === NULL) $compare_date = time();
		
		$date_of_birth = getdate($date_of_birth);
		$compare_date = getdate($compare_date);
		
		$age = $compare_date['year'] - $date_of_birth['year'];
		
		// If we have not had our birthday this year, subtract a year
		if ($date_of_birth['yday'] > $compare_date['yday'])
		{
			$age--;
		}
		
		// If our age is negative, reset to 0
		if ($age < 0)
		{
			$age = 0;
		}
		
		return $age;
	}
}

?>
