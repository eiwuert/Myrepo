<?php
/**
 * @author Justin Foell
 * @package Date
 */
class Date_PayDateMonthlyOnWeekAndDay_1 extends Date_PayDateModel_1
{
	public function __construct($model_name, $week_1, $day_of_week)
	{
		parent::__construct($model_name);
		$this->setWeek1($week_1);
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
	   		//get the 1st of the month
			$date_info = getdate($timestamp);
			$date = mktime(0, 0, 0, $date_info['mon'] + $month_increment, 1, $date_info['year']);
			if($this->Week1 == 5)//last dow of month
			{
				$date = strtotime("last {$this->DayOfWeek}", $date);
			}
			else
			{
				$date = $this->getDateInWeek($date, $this->Week1);
			}
			$month_increment++;
		}
		return $date;
	}

	/**
	 * Based loosely on Date_BankHolidays_1::getNthDOW()
	 *
	 * @param int $date 1st of month timestamp
	 */ 
	private function getDateInWeek($date, $week_num)
	{
		if(strtolower(date('l', $date)) != $this->DayOfWeek)
		{
			$date = strtotime("this {$this->DayOfWeek}", $date);
		}

		//so if they say 3rd week, we just add to to the current
		$week_num--;
		return strtotime("+{$week_num} weeks", $date);
	}	
}

?>