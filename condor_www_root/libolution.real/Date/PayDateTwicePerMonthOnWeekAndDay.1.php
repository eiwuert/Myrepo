<?php
/**
 * @author Justin Foell
 * @package Date
 */
class Date_PayDateTwicePerMonthOnWeekAndDay_1 extends Date_PayDateModel_1
{
	public function __construct($model_name, $week_1, $week_2, $day_of_week)
	{
		parent::__construct($model_name);
		$this->setWeek1($week_1);
		$this->setWeek2($week_2);
		$this->setDayOfWeek($day_of_week);
	}

	public function nextPayDate($timestamp)
	{
		$date = $timestamp;
		$month_increment = $week_increment = 0;
		$first_of_month = NULL;
		while($date <= $timestamp)
		{
			//get the 1st of the month
			$week = ($week_increment % 2) + 1;
			if($week == 1)
			{
				$date_info = getdate($date);
				$first_of_month = mktime(0, 0, 0, $date_info['mon'] + $month_increment, 1, $date_info['year']);
			}
			$date = $this->getDateInWeek($first_of_month, $this->{'Week' . $week});
			$week_increment++;
            //only increment the month if both weeks have been tried
            if($week == 2)
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
		if(date('l', $date) != $this->DayOfWeek)
		{
			$date = strtotime("this {$this->DayOfWeek}", $date);
		}

		//so if they say 3rd week, we just add to to the current
		$week_num--;
		$date = strtotime("+{$week_num} weeks", $date);

		return $date;		
	}
}

?>