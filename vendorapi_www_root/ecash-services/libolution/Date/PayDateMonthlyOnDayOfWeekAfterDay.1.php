<?php
/**
 * @author Justin Foell
 * @package Date
 */
class Date_PayDateMonthlyOnDayOfWeekAfterDay_1 extends Date_PayDateModel_1
{
	public function __construct($model_name, $day_of_month_1, $day_of_week)
	{
		parent::__construct($model_name);
		$this->setDayOfMonth1($day_of_month_1);
		$this->setDayOfWeek($day_of_week);
	}

	/**
	 * 
	 */
	public function nextPayDate($timestamp)
	{
		$date = $timestamp;
		$month_increment = 0;
		while($date <= $timestamp)
		{
			//get the Nth of the month
			$date_info = getdate($timestamp);
			$date = mktime(0, 0, 0, $date_info['mon'] + $month_increment, $this->DayOfMonth1, $date_info['year']);

			//then get the next DOW as specified
			if(date('l', $date) != $this->DayOfWeek)
			{
				$date = strtotime("next {$this->DayOfWeek}", $date);
			}
			$month_increment++;
		}
		return $date;
	}
}

?>