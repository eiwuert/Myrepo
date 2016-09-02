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
	 * @param int $date1 unix timestamp
	 * @param int $date2 unix timestamp
	 * @return int number of days difference
	 */
	public static function dateDiff($date1, $date2)
	{
		return ($date1 > $date2)
			? unixtojd($date1) - unixtojd($date2)
			: unixtojd($date2) - unixtojd($date1);
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
}

?>
