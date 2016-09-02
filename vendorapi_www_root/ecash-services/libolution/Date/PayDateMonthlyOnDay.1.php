<?php
/**
 * @author Justin Foell
 * @package Date
 */
class Date_PayDateMonthlyOnDay_1 extends Date_PayDateModel_1
{
	public function __construct($model_name, $day_of_month_1)
	{
		parent::__construct($model_name);
		$this->setDayOfMonth1($day_of_month_1);
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
			$date_info = getdate($date);
			//get the 1st of the month
			$date = mktime(0, 0, 0, $date_info['mon'] + $month_increment, 1, $date_info['year']);
			$date = strtotime("{$this->DayOfMonth1} ". date('F',$date), $date);
			$month_increment++;			
		}
		return $date;
	}
}

?>