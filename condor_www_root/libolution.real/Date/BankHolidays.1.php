<?php
/**
 * Calculates US Bank Holidays
 *
 * Tested against {@link http://www.opm.gov/fedhol/}
 *
 * @author Justin Foell
 * @package Date
 */


/**
 * @package Date
 */
class Date_BankHolidays_1 extends Object_1 implements Iterator
{
	const FORMAT_TIMESTAMP = NULL;
	const FORMAT_US = 'm-d-Y';
	const FORMAT_EUR = 'd-m-Y';
	const FORMAT_ISO = 'Y-m-d'; //most strtotime friendly

	private $start_date;
	private $format;
	private $this_year;
	private $holiday_cache = array();
	private $holiday_key;
	private $holidays = array('NewYearsDay',
			'MLK',
			'PresidentsDay',
			'MemorialDay',
			'IndependenceDay',
			'LaborDay',
			'ColumbusDay',
			'VeteransDay',
			'ThanksgivingDay',
			'Christmas');
	private $holiday_start_idx = NULL;

	/**
	 * Constructor
	 *
	 * You can use this class in one of two ways:
	 * <code>
	 * //gets one year
	 * $holidays = new Date_BankHolidays_1();
	 * $holiday_array = $holidays->getHolidayArray();
	 * //gets next new years day, if today is in 2007, return Jan 1, 2008 (or relative observed day)
	 * $holidays = new Date_BankHolidays_1();
	 * $new_years = $holidays->getNewYears();
	 * </code>
	 *
	 * @param int $start_date timestamp to start holiday list at, defaults to 'now'
	 * @param string $format a PHP date format from date() {@link http://php.net/date} or one of the FORMAT constants from this class, NULL for unix timestamp (default)
	 */
	public function __construct($start_date = NULL, $format = self::FORMAT_TIMESTAMP)
	{
		$this->setStartDate(($start_date ? $start_date : time()));
		$this->setFormat($format);

		//this is for those who want individual holidays w/o specifying year (assuming next holiday after today)
		$this->this_year = idate('Y');
	}

	/**
	 * Set the format if you didn't like what you put in the constructor
	 *
	 * @param const $format a PHP date format from date() {@link http://php.net/date} or one of the FORMAT constants from this class, NULL for unix timestamp (default)
	 */
	public function setFormat($format)
	{
		if($format)
		{
			$this->format = $format;
		}
		else
		{
			$this->format = self::FORMAT_TIMESTAMP;
		}
	}

	/**
	 * Gets an array of holidays based on the StartDate (passed to constructor) and EndDate
	 *
	 * If no StartDate or EndDate specified, defaults starting today until one year from today
	 *
	 * @param int $end_date unix timestamp to end at (default now +1 year)
	 * @return Array US federal observed holidays for date range
	 */
	public function getHolidayArray($end_date = NULL)
	{
		$end_date = $end_date ? $end_date : strtotime('+1 year', $this->start_date);

		if(!$this->start_date || $end_date < $this->start_date)
			throw new Exception('StartDate must be set to a valid timestamp and must be before EndDate');

		$this->rewind();

		$holidays = array();

		$holiday = $this->current();
		$timestamp = $this->value($this->key());

		while($timestamp <= $end_date)
		{
			$holidays[] = $holiday;
			$this->next();
			$holiday = $this->current();
			$timestamp = $this->value($this->key());
		}

		$this->rewind();
		return $holidays;
	}

	/**
	 * Formats timestamps into date strings
	 *
	 * ChrisS added the formatting to this class a long time ago.
	 * I'm not sure who uses it, but I think ecash might use one of
	 * these for their standard holiday array
	 *
	 * @param int $timestamp unix timestamp
	 * @return mixed timestamp or formatted date
	 */
	private function format($timestamp)
	{
		if($this->format != self::FORMAT_TIMESTAMP)
		{
			return date($this->format, $timestamp);
		}
		return $timestamp;
	}

	/** Sets the start date. Resets it to midnight on the timestamp supplied.
	 *
	 * @param int $start_date unix timestamp
	 * @return void
	 */
	public function setStartDate($start_date)
	{
		$date = getdate($start_date);
		$this->start_date = mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']);

		//initialize the holiday_key
		$this->rewind();
		$this->holiday_cache = array();
		$this->holiday_start_idx = NULL;
	}

	public function getStartDate()
	{
		return $this->start_date;
	}

	/**
	 *
	 * @param int $plus nth week of month
	 */
	private function getNthDOW($month, $day, $year, $plus = NULL)
	{
		//do this to save the original parameter
		$temp_year = $year;
		if($temp_year === NULL)
			$temp_year = $this->this_year;

		$date = strtotime("1 {$month} {$temp_year}");

		if(date('l', $date) != $day)
		{
			$date = strtotime("this {$day}", $date);
		}

		if ($plus != NULL)
		{
			$date = strtotime("+{$plus} weeks", $date);
		}

		if($year === NULL && $date < $this->start_date)
			return $this->getNthDOW($month, $day, $temp_year + 1, $plus);

		return $date;
	}

	private function getObserved($timestamp)
	{
		if(date('D', $timestamp) == 'Sat')
		{
			return strtotime('-1 day', $timestamp);
		}
		elseif(date('D', $timestamp) == 'Sun')
		{
			return strtotime('+1 day', $timestamp);
		}
		return $timestamp;
	}

	public function getNewYearsDay($year = NULL)
	{
		//do this to save the original parameter
		$temp_year = $year;
		if($temp_year === NULL)
			$temp_year = $this->this_year;

		$new_years = strtotime("1 January {$temp_year}");
		$observed = $this->getObserved($new_years);

		if($year === NULL && $observed < $this->start_date)
			return $this->getNewYearsDay($temp_year + 1);

		return $observed;
	}

	public function getMLK($year = NULL)
	{
		return $this->getNthDOW('January', 'Monday', $year, 2);
	}

	public function getPresidentsDay($year = NULL)
	{
		return $this->getNthDOW('February', 'Monday', $year, 2);
	}

	public function getMemorialDay($year = NULL)
	{
		//do this to save the original parameter
		$temp_year = $year;
		if($temp_year === NULL)
			$temp_year = $this->this_year;

		$may31 = strtotime("31 May {$temp_year}");
		$memorial = NULL;
		if(date('D', $may31) == 'Mon')
		{
			$memorial = $may31;
		}
		else
		{
			$memorial = strtotime('last Monday', $may31);
		}

		if($year === NULL && $memorial < $this->start_time)
			return $this->getMemorialDay($temp_year + 1);

		return $memorial;
	}

	public function getIndependenceDay($year = NULL)
	{
		//do this to save the original parameter
		$temp_year = $year;
		if($temp_year === NULL)
			$temp_year = $this->this_year;

		$july4 = strtotime("4 July {$temp_year}");
		$observed = $this->getObserved($july4);

		if($year === NULL && $observed < $this->start_date)
			return $this->getIndependenceDay($temp_year + 1);

		return $observed;
	}

	public function getLaborDay($year = NULL)
	{
		return $this->getNthDOW('September', 'Monday', $year);
	}

	public function getColumbusDay($year = NULL)
	{
		return $this->getNthDOW('October', 'Monday', $year, 1);
	}

	public function getVeteransDay($year = NULL)
	{
		//do this to save the original parameter
		$temp_year = $year;
		if($temp_year === NULL)
			$temp_year = $this->this_year;

		$veterans = strtotime("11 November {$temp_year}");
		$observed = $this->getObserved($veterans);

		if($year === NULL && $observed < $this->start_date)
			return $this->Veterans_Day($this->year + 1);

		return $observed;
	}

	public function getThanksgivingDay($year = NULL)
	{
		return $this->getNthDOW('November', 'Thursday', $year, 3);
	}

	public function getChristmas($year = NULL)
	{
		//do this to save the original parameter
		$temp_year = $year;
		if($temp_year === NULL)
			$temp_year = $this->this_year;

		$xmas = strtotime("25 December {$year}");
		$observed = $this->getObserved($xmas);

		if($year === NULL && $observed < $this->start_time)
			return $this->getChristmas($this->year + 1);

		return $observed;
	}

	/**
	 * Get the holiday specified.
	 *
	 * This method gets a holiday, indexed by $this->holiday_key.
	 *
	 * @param int $offset key offset to be passed in -- used for recursion to locate the first holiday after the StartDate and to store that holiday as index 0
	 * @return int unix timestamp of the holiday found for index at $this->holiday_key
	 *
	 */
	private function getHoliday($offset = 0)
	{
		if(!isset($this->holiday_cache[$this->holiday_key]))
		{
			//this is kind of a hack (albeit useful) b/c holiday_start_idx will be null the first time around
			$idx = $offset + $this->holiday_start_idx;

			//figure out what method we're going to call (use modulus to repeat through the holidays array)
			$method = $this->holidays[$idx % count($this->holidays)];

			//get the year we're going to call the method with
			$year = date('Y', $this->start_date) + floor($idx / count($this->holidays));

			$holiday = call_user_func_array(array($this, 'get' . $method), $year); //next holiday

			if($holiday >= $this->start_date)
			{
				//save the holiday_start_idx so we know which is the first holiday after
				//the start date
				if($this->holiday_start_idx === NULL)
				{
					$this->holiday_start_idx = $offset;
				}
				$this->holiday_cache[$this->holiday_key] = $holiday;
			}
			else
			{
				$this->getHoliday(++$offset);
			}
		}
		return $this->holiday_cache[$this->holiday_key];
	}

	/**
	 * Get the raw (timestamp) value
	 *
	 * This method is for getHolidays to be able to combine two methods $this->value($this->key)
	 * to get an unformatted (unix timestamp) for end_date comparison
	 *
	 * @param int $key
	 * @return int unix timestamp
	 */
	public function value($key)
	{
		return $this->holiday_cache[$key];
	}

	//methods to implement Iterator
	public function current()
	{
		return $this->format($this->getHoliday());
	}

	public function key()
	{
		return $this->holiday_key;
	}

	public function next()
	{
		$this->holiday_key++;
		return $this->format($this->getHoliday($this->holiday_key));
	}

	public function rewind()
	{
		$this->holiday_key = 0;
	}

	/**
	 * Whether or not there are more holidays (always TRUE)
	 *
	 * While returning TRUE always may seem simplistic and maybe
	 * dangerous it is true, you can get as many holidays as you'd
	 * like, ad nauseum.  I suppose a 32-bit int will run out of room
	 * at some point, but then you're on your own.
	 *
	 * @return boolean always TRUE
	 */
	public function valid()
	{
		return TRUE;
	}
}

?>
