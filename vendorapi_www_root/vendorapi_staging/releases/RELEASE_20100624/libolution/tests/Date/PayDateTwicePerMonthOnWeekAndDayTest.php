<?php

class Date_PayDateTwicePerMonthOnWeekAndDayTest extends PHPUnit_Framework_TestCase
{
	public function test1stAnd3rdMondayDD()
	{
		//$model_name, $direct_deposit, $week_1, $week_2, $day_of_week
		$start_date = strtotime('2008-02-01');
		$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::TWICE_PER_MONTH_ON_WEEK_AND_DAY, 'monday', NULL, NULL, NULL, 1, 3);
		$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1(), TRUE), $start_date);
		$this->assertEquals(date('Y-m-d', strtotime('2008-02-04')), date('Y-m-d', $calc->current()));
		$this->assertEquals(date('Y-m-d', strtotime('2008-02-15')), date('Y-m-d', $calc->next())); //don't forget president's day!
	}

	public function test1stAnd3rdMondayNoDD()
	{
		//$model_name, $direct_deposit, $week_1, $week_2, $day_of_week
		$start_date = strtotime('2008-02-01');
		$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::TWICE_PER_MONTH_ON_WEEK_AND_DAY, 'monday', NULL, NULL, NULL, 1, 3);
		$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1(), FALSE), $start_date);
		$this->assertEquals(date('Y-m-d', strtotime('2008-02-05')), date('Y-m-d', $calc->current()));
		$this->assertEquals(date('Y-m-d', strtotime('2008-02-19')), date('Y-m-d', $calc->next())); //don't forget president's day!
	}
}

?>