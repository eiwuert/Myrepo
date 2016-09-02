<?php
/**
 * @author Justin Foell
 * @package Date
 */
class Date_PayDateTwicePerMonthOnDays_1 extends Date_PayDateModel_1
{
	public function __construct($model_name, $day_of_month_1, $day_of_month_2)
	{
		parent::__construct($model_name);
		//just incase somehow the first date is the later one (not normally allowed on website, but in ecash who knows) [JustinF]
		if($day_of_month_1 > $day_of_month_2)
		{
			$this->setDayOfMonth1($day_of_month_2);
			$this->setDayOfMonth2($day_of_month_1);
		}
		else
		{
			$this->setDayOfMonth1($day_of_month_1);
			$this->setDayOfMonth2($day_of_month_2);
		}
	}

	/**
	 * @todo double check the input from the paydate wizard (can DayOfMonth2 be less than DayOfMonth1?)
	 */
	public function nextPayDate($timestamp)
	{
		$date = $timestamp;
		$month_increment = $dom_increment = 0;
		while ($date <= $timestamp)
		{
			//get the 1st of the month
			$date_info = getdate($date);
			$date = mktime(0, 0, 0, $date_info['mon'] + $month_increment, 1, $date_info['year']);
			$dom = ($dom_increment % 2) + 1; //alternate between 1 and 2
			$date = $this->normalizeMonth($date, $this->{'DayOfMonth' . $dom});
			$dom_increment++;
			//only increment the month if both weeks have been tried
			if($dom == 2)
				$month_increment++;
		}
		return $date;
	}

}

?>