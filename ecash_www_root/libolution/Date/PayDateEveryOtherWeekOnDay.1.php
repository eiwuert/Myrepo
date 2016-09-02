<?php
/**
 * @author Justin Foell
 * @package Date
 */
class Date_PayDateEveryOtherWeekOnDay_1 extends Date_PayDateModel_1
{
	public function __construct($model_name, $day_of_week, $last_pay_date)
	{
		parent::__construct($model_name);
		$this->setDayOfWeek($day_of_week);
		$this->setLastPayDate($last_pay_date);
	}


	/**
	 * @TODO double check valid last pay date input with paydate wizard
	 * 
	 * @param int $timestamp last pay date
	 * @return int returns timestamp of next pay date (+2 weeks) 
	 */
	public function nextPayDate($timestamp)
	{
		//a minor hack to overcome the prefech in the paydate calculator (where it rewinds one date)
		//fast fowarding one date in any case should not matter
		$timestamp = strtotime('+1 day', $timestamp);
		
		//+2 weeks will not work in the case where the last pay date entered was
		//offset by a weekend or holiday
		$next_week = strtotime("next {$this->DayOfWeek}", $timestamp);
		return strtotime('+1 week', $next_week); //(week after)
	}

}

?>
