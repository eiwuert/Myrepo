<?php

require_once('autoload_setup.php');

class Date_PayDateMonthlyOnDayOfWeekAfterDayTest extends PHPUnit_Framework_TestCase
{
	public function test1stFridayDD()
	{
		//$model_name, $direct_deposit, $day_of_month_1, $day_of_week
		$start_date = strtotime('2008-02-01');
		$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::MONTHLY_ON_DAY_OF_WEEK_AFTER_DAY, 'friday', NULL, 1);
		$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1(), TRUE), $start_date);
		$this->assertEquals(date('Y-m-d', strtotime('2008-02-08')), date('Y-m-d', $calc->current()));
		$this->assertEquals(date('Y-m-d', strtotime('2008-03-07')), date('Y-m-d', $calc->next())); //don't forget president's day!
		$this->assertEquals(date('Y-m-d', strtotime('2008-04-04')), date('Y-m-d', $calc->next())); //don't forget president's day!
	}
}

?>