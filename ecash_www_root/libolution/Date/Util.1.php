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

        /**
         * For php < 5.3
	 * Determines number of years elapsed between two dates.
	 *
	 * @param int $start_date
         * @param int $compare_date
	 * @return int
	 */
	public static function getYearsElapsed($start_date, $compare_date = NULL)
	{
		if (!(self::isTimestamp($start_date)))
                {
                        $start_date = strtotime($start_date);
                }

                if ($compare_date === NULL) $compare_date = time();

                if (!(self::isTimestamp($compare_date)))
                {
                        $compare_date = strtotime($compare_date);
                }

                $start_date = getdate($start_date);
                $compare_date = getdate($compare_date);

                $years = $compare_date['year'] - $start_date['year'];

                if (
                        ($start_date['mon'] > $compare_date['mon'])
                        ||
                        ($start_date['mon'] == $compare_date['mon'] && $start_date['mday'] > $compare_date['mday'])
                )
                {
                        $years--;
                }

                if ($years < 0)
                {
                        $years = 0;
                }

                return $years;
	}

        /**
         * For php < 5.3
	 * Determines number of months elapsed between two dates.
	 *
	 * @param int $start_date
         * @param int $compare_date
	 * @return int
	 */
	public static function getMonthsElapsed($start_date, $compare_date = NULL)
	{
		if (!(self::isTimestamp($start_date)))
                {
                        $start_date = strtotime($start_date);
                }

                if ($compare_date === NULL) $compare_date = time();

                if (!(self::isTimestamp($compare_date)))
                {
                        $compare_date = strtotime($compare_date);
                }

                $years = self::getYearsElapsed($start_date, $compare_date);

                $start_date = getdate($start_date);
                $compare_date = getdate($compare_date);

                $months = $compare_date['mon'] - $start_date['mon'];

                if ($months >= 0)
                {
                        if ($compare_date['mday'] < $start_date['mday'])
                        {
                                $months--;
                        }
                }
                else
                {
                        if ($compare_date['mday'] >= $start_date['mday'])
                        {
                                $months = 12 + $months;
                        }
                        elseif ($compare_date['mday'] < $start_date['mday'])
                        {
                                $months = 12 + $months - 1;
                        }
                }

                return $years*12 + $months;
	}

        /**
         * For php < 5.3
	 * Determines number of years and months elapsed between two dates.
	 *
	 * @param int $start_date
         * @param int $compare_date
	 * @return int
	 */
	public static function getYearsMonthsElapsed($start_date, $compare_date = NULL)
	{
		if (!(self::isTimestamp($start_date)))
                {
                        $start_date = strtotime($start_date);
                }

                if ($compare_date === NULL) $compare_date = time();

                if (!(self::isTimestamp($compare_date)))
                {
                        $compare_date = strtotime($compare_date);
                }

                $years = self::getYearsElapsed($start_date, $compare_date);

                $start_date = getdate($start_date);
                $compare_date = getdate($compare_date);

                $months = $compare_date['mon'] - $start_date['mon'];

                if ($months >= 0)
                {
                        if ($compare_date['mday'] < $start_date['mday'])
                        {
                                $months--;
                        }
                }
                else
                {
                        if ($compare_date['mday'] >= $start_date['mday'])
                        {
                                $months = 12 + $months;
                        }
                        elseif ($compare_date['mday'] < $start_date['mday'])
                        {
                                $months = 12 + $months - 1;
                        }
                }

                $years_months = array("yrs" => $years, "mos" => $months);

                return $years_months;
	}

        // for php >= 5.3
        /*
        protected static function monthsElapsed($start_date)
        {
                if (Date_Util_1::isTimestamp($start_date))
                {
                        $start_date = date("Y-m-d", $start_date);
                }

                $dt1 = new DateTime($start_date);
                $dt2 = new DateTime();

                $years = $dt2->diff($dt1)->y;
                $months = $dt2->diff($dt1)->m;

                return $years*12 + $months;
        }
        */
}

?>
