<?php
/**
 * @author Justin Foell
 * @package Date
 */
class Date_PayDateWeeklyOnDay_1 extends Date_PayDateModel_1
{
	public function __construct($model_name, $day_of_week)
	{
		parent::__construct($model_name);
		$this->setDayOfWeek($day_of_week);
	}

	public function nextPayDate($timestamp)
	{
		return strtotime("next {$this->DayOfWeek}", $timestamp);
	}
}

?>
