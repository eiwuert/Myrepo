<?php

class Date_Normalizer_1
{
	private $holiday_iterator;
	private $holiday_cache;
	private $next_holiday;
	private $holiday_start_date;

	public function __construct(Iterator $holiday_iterator, $holiday_start_date = NULL)
	{
		$this->holiday_iterator = $holiday_iterator;
		$this->holiday_start_date = ($holiday_start_date === NULL) ? time() : $holiday_start_date;
		
		//incase a developer forgot or messed up the params to the holiday iterator
		if($this->holiday_iterator instanceof Date_BankHolidays_1)
		{
			$this->holiday_iterator->setFormat(Date_BankHolidays_1::FORMAT_TIMESTAMP);
			$this->holiday_iterator->setStartDate($this->holiday_start_date);
		}
		
		//clear the holiday cache before adding our first
		$this->reset();
		
		//insure the holiday format is unix timestamp
		$ts = $this->holiday_iterator->current();

		//insures only timestamps (negative allowed)
		if(!preg_match('/^[-]?[0-9]+$/', $ts))
		{
			throw new Exception('Holiday iterator must contain holidays in Unix Timestamp format');
		}
		$this->holiday_cache[] = $this->next_holiday = $this->normalizeTime($ts);		
	}
	
	/**
	 * This is just incase some idiot or idiotic test case
	 * passes in a timestamp that doesn't have hours, minutes, seconds zeroed
	 * 
	 * @param int $timestamp unix timestamp of holiday
	 * @return int unix timestamp of holiday with hours, minutes, seconds zeroed
	 */
	public static function normalizeTime($timestamp)
	{
		$date_info = getdate($timestamp);
		if($date_info['hours'] != 0 || $date_info['minutes'] != 0 || $date_info['seconds'] != 0)
		{
			return mktime(0, 0, 0, $date_info['mon'], $date_info['mday'], $date_info['year']);
		}
		return $timestamp;
	}
	
	/**
	 * Indicates whether the given timestamp falls on a holiday
	 * When used in conjunction with Date_BankHolidays_1 you don't
	 * have to pre-fill the holiday array with a certain number of
	 * holidays.  It will iterate through and get the next applicable
	 * holiday for wherever you're at.
	 *
	 * @param int $timestamp
	 * @return bool
	 */
	public function isHoliday($timestamp)
	{
		$timestamp = self::normalizeTime($timestamp);
		
		//if we've gone past the last holiday, get another
		while($timestamp > $this->next_holiday && $this->holiday_iterator->valid())
		{
			$this->holiday_iterator->next();
			$this->holiday_cache[] = $this->next_holiday = self::normalizeTime($this->holiday_iterator->current());
		}
		
		if(in_array($timestamp, $this->holiday_cache))
		{
			return TRUE;
		}
		return FALSE;		
	}
	
	/**
	 * Indicates whether the given timestamp falls on a weekend
	 * @param int $timestamp UNIX timestamp
	 * @return bool
	 */
	public function isWeekend($timestamp)
	{
		$int_dow = idate('w', $timestamp);
		if($int_dow === 0 || $int_dow === 6)
		{
			return TRUE;
		}
		return FALSE;
	}
	
	public function normalize($timestamp, $forward = TRUE)
	{
		$timestamp = self::normalizeTime($timestamp);

		if($this->holiday_start_date > $timestamp && $this->holiday_iterator instanceof Date_BankHolidays_1)
		{
			$this->reset();
			$this->holiday_start_date = $timestamp;
			$this->holiday_iterator->setStartDate($this->holiday_start_date);
		}

		//skip forward or back if on a weekend or holiday
		while($this->isWeekend($timestamp) || $this->isHoliday($timestamp))
		{
			$timestamp = $forward ? strtotime('+1 day', $timestamp) : strtotime('-1 day', $timestamp);
		}

		return $timestamp;
	}

	public function reset()
	{
		$this->holiday_cache = array();
		$this->holiday_iterator->rewind();
		$this->next_holiday = NULL;
	}

	/**
	 * Advances given timestamp X business days
	 * 
	 * @param int $timestamp unixtime of date to start with
	 * @param int $days number of days to advance
	 * @return int timestamp of new date
	 */
	public function advanceBusinessDays($timestamp, $days)
	{
		return $this->seekBusinessDays($timestamp, $days, TRUE);
	}

	/**
	 * Rewinds given timestamp X business days
	 *	
	 * @param int $timestamp unixtime of date to start with
	 * @param int $days number of days to rewind
	 * @return int timestamp of new date
	 */
	public function rewindBusinessDays($timestamp, $days)
	{
		return $this->seekBusinessDays($timestamp, $days, FALSE);
	}

	/**
	 * Advances/Rewinds given timestamp X business days
	 * 
	 * @param int $timestamp unixtime of date to start with
	 * @param int $days number of days to advance/rewind
	 * @param bool $forward whether to advance/rewind, default advance (TRUE)
	 * @return int timestamp of new date
	 */
	public function seekBusinessDays($timestamp, $days, $forward = TRUE)
	{
		$count = 0;
		while($count < $days)
		{
			$timestamp = $forward ? strtotime('+1 day', $timestamp) : strtotime('-1 day', $timestamp);
			$timestamp = $this->normalize($timestamp, $forward);
			$count++;
		}
		return $timestamp;
	}
	
	public function endOfDay($timestamp)
	{
		return self::normalizeTime(strtotime('+1 day', $timestamp));
	}
}

?>
