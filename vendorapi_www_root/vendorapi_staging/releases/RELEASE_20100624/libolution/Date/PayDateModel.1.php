<?php

/**
 * Abstract parent of all the different types of PayDateModels.
 *
 * It has a factory method getModel() to construct a
 * PayDateModel based on model name.  The models, besides
 * containing the PayDate 'pattern' data, also implement the
 * abstract method nextPayDate() which will contain the logic to
 * calculate the next PayDate in the sequence.
 *
 * @author Justin Foell
 * @package Date
 */
abstract class Date_PayDateModel_1 extends Object_1
{
	const WEEKLY_ON_DAY = 'weekly_on_day';
	const EVERY_OTHER_WEEK_ON_DAY = 'every_other_week_on_day';
	const TWICE_PER_MONTH_ON_DAYS = 'twice_per_month_on_days';
	const TWICE_PER_MONTH_ON_WEEK_AND_DAY = 'twice_per_month_on_week_and_day';
	const MONTHLY_ON_DAY = 'monthly_on_day';
	const MONTHLY_ON_WEEK_AND_DAY = 'monthly_on_week_and_day';
	const MONTHLY_ON_DAY_OF_WEEK_AFTER_DAY = 'monthly_on_day_of_week_after_day';

	private static $old_name_map = array("dw" => self::WEEKLY_ON_DAY,
					"dwpd" => self::EVERY_OTHER_WEEK_ON_DAY,
					"dmdm" => self::TWICE_PER_MONTH_ON_DAYS,
					"wwdw" => self::TWICE_PER_MONTH_ON_WEEK_AND_DAY,
					"dm" => self::MONTHLY_ON_DAY,
					"wdw" => self::MONTHLY_ON_WEEK_AND_DAY,
					"dwdm" => self::MONTHLY_ON_DAY_OF_WEEK_AFTER_DAY);

	private $model_name;
	private $day_of_week;
 	private $last_pay_date;
	private $day_of_month_1;
	private $day_of_month_2;
	private $week_1;
	private $week_2;
	private $direct_deposit;

	/**
	 * Constructor
	 *
	 * This object encompasses the stuff that used to be in the
	 * pay date model array
	 *
	 * @todo Needs validation
	 */
	protected function __construct($model_name)
	{
		$this->setModelName($model_name);
	}

	/**
	 * Factory method to get the correct type of PayDateModel instance.
	 *
	 * Feed in the parameters from the database or pay date wizard.
	 * Since all parameters but $model_name are optional see the
	 * included method source to determine what is required for a
	 * specific model type.  An exception will be thrown if parameters
	 * are missing or incorrect for the given $model_name.
	 * {@source }
	 *
	 * @param string $model_name name of PayDateModel, new ('weekly_on_day') or old ('dw') style
	 * @param string $day_of_week name of DOW like 'wed' or 'wednesday'
	 * @param string $last_pay_date strtotime friendly string of last paydate, like 'YYYY-MM-DD'
	 * @param int $day_of_month_1 first date of month paydate
	 * @param int $day_of_month_2 second date of month paydate
	 * @param int $week_1 first number of week paid in
	 * @param int $week_2 second number of week paid in
	 */
	public static function getModel($model_name,
									$day_of_week = NULL,
									$last_pay_date = NULL,
									$day_of_month_1 = NULL,
									$day_of_month_2 = NULL,
									$week_1 = NULL,
									$week_2 = NULL)
	{
		$model_name = self::getNewModelName($model_name);

		switch($model_name)
		{
			case self::WEEKLY_ON_DAY:
				return new Date_PayDateWeeklyOnDay_1($model_name, $day_of_week);

			case self::EVERY_OTHER_WEEK_ON_DAY:
				return new Date_PayDateEveryOtherWeekOnDay_1($model_name, $day_of_week, $last_pay_date);

			case self::TWICE_PER_MONTH_ON_DAYS:
				return new Date_PayDateTwicePerMonthOnDays_1($model_name, $day_of_month_1, $day_of_month_2);

			case self::TWICE_PER_MONTH_ON_WEEK_AND_DAY:
				return new Date_PayDateTwicePerMonthOnWeekAndDay_1($model_name, $week_1, $week_2, $day_of_week);

			case self::MONTHLY_ON_DAY:
				return new Date_PayDateMonthlyOnDay_1($model_name, $day_of_month_1);

			case self::MONTHLY_ON_WEEK_AND_DAY:
				return new Date_PayDateMonthlyOnWeekAndDay_1($model_name, $week_1, $day_of_week);

			case self::MONTHLY_ON_DAY_OF_WEEK_AFTER_DAY:
				return new Date_PayDateMonthlyOnDayOfWeekAfterDay_1($model_name, $day_of_month_1, $day_of_week);

			default:
				throw new Exception("Unknown model name: {$model_name}");
		}
	}

	public static function getNewModelName($name)
	{
		$model_name = strtolower($name);
		// Check for old style model name

		if( isset(self::$old_name_map[$model_name]) )
			return self::$old_name_map[$model_name];

		return $model_name;
	}

	public function getModelName()
	{
		return $this->model_name;
	}

	/**
	 * Set the model name based on the new or old-style model.
	 *
	 * Not sure if the old-style models are even used anymore.
	 *
	 * @todo we may want to add an exception if the model name doesn't exist
	 */
	protected function setModelName($name)
	{
		$this->model_name = $name;
	}

	//other public accessor methods
	public function getDayOfWeek()
	{
		return $this->day_of_week;
	}

	protected function setDayOfWeek($dow)
	{
		static $dow_array = array("sun" => "sunday",
								  "mon" => "monday",
								  "tue" => "tuesday",
								  "wed" => "wednesday",
								  "thu" => "thursday",
								  "fri" => "friday",
								  "sat" => "saturday");

		$dow = strtolower($dow);

		if(!($in_normal = in_array($dow, $dow_array)) && !(in_array($dow, array_flip($dow_array))))
		{
			throw new Exception("{$dow} is not a valid day of week");
		}

		$this->day_of_week = $in_normal ? $dow : $dow_array[$dow];
	}

	public function getLastPayDate()
	{
		return $this->last_pay_date;
	}

	/**
	 * @param $date last paydate timestamp or strtotime friendly string
	 */
	protected function setLastPayDate($date)
	{
		if(preg_match('/^[-]?[0-9]+$/', $date))
		{
			$this->last_pay_date = $date;
		}
		else
		{			
			$timestamp = strtotime($date);
			if(($timestamp === FALSE || $timestamp == -1))
			{
				throw new Exception("Date is not valid or was not recognized by strtotime: {$date}");
			}

			$this->last_pay_date = $timestamp;
		}
	}

	public function getDayOfMonth1()
	{
		return $this->day_of_month_1;
	}

	public function setDayOfMonth1($dom)
	{
		$this->setDayOfMonth(1, $dom);
	}

	public function getDayOfMonth2()
	{
		return $this->day_of_month_2;
	}

	public function setDayOfMonth2($dom)
	{
		$this->setDayOfMonth(2, $dom);
	}

	protected function setDayOfMonth($num, $dom)
	{
		if($dom == 33) //retard handler for [#17795]
		{
			$dom = 1;
		}

		if(!is_numeric($dom) || $dom < 1 || $dom > 32 ) //32 == last day of month
		{
			throw new Exception("{$dom} is not valid for DayOfMonth");
		}

		if ($num !== 1 && $num !== 2)
		{
			throw new Exception("Property DayOfMonth{$num} does not exist.");
		}

		$this->{'day_of_month_' . $num} = $dom;
	}

	public function getWeek1()
	{
		return $this->week_1;
	}

	protected function setWeek1($week)
	{
		$this->setWeek(1, $week);
	}

	public function getWeek2()
	{
		return $this->week_2;
	}

	protected function setWeek2($week)
	{
		$this->setWeek(2, $week);
	}

	protected function setWeek($num, $week)
	{
		if(!is_numeric($week) || $week < 1 || $week > 5)
		{
			throw new Exception("{$week} is not valid for Week");
		}
		if($num !== 1 && $num !== 2)
		{
			throw new Exception("Property Week{$num} does not exist");
		}

		$this->{'week_' . $num} = $week;
	}


	/**
	 *  This method checks to see if the day of month exceeds the
	 *  boundary of the current month.  i.e. 29th, 30th, and 31st for
	 *  months that do not have those days.  It will return the last
	 *  day of the month if the boundary is exceeded.
	 *
	 * @param int $timestamp last pay date
	 * @param int $dom day of month (1-32) [32 == last day of month]
	 * @return int timestamp of (possibly corrected) day of month
	 */
	protected function normalizeMonth($timestamp, $dom)
	{
		$date_info = getdate($timestamp);

		// ensure that DOM doesn't pass the end of the month
		$last_day = date('t', $timestamp);
		if ($dom > $last_day) $dom = $last_day;

		return mktime(0, 0, 0, $date_info['mon'], $dom, $date_info['year']);
	}

	public abstract function nextPayDate($timestamp);
}

?>
