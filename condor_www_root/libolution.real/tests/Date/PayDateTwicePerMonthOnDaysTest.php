<?php

require_once('autoload_setup.php');

class Date_PayDateTwicePerMonthOnDaysTest extends PHPUnit_Framework_TestCase
{
	public function test15And30FebruaryLeapYear()
	{
		//$model_name, $direct_deposit, $day_of_month_1, $day_of_month_2
		$start_date = strtotime('2008-02-01');
		$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::TWICE_PER_MONTH_ON_DAYS, NULL, NULL, 15, 30);
		$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1(), TRUE), $start_date);
		$this->assertEquals(date('Y-m-d', strtotime('2008-02-15')), date('Y-m-d', $calc->current()));
		$this->assertEquals(date('Y-m-d', strtotime('2008-02-29')), date('Y-m-d', $calc->next()));
		$this->assertEquals(date('Y-m-d', strtotime('2008-03-14')), date('Y-m-d', $calc->next()));
		$this->assertEquals(date('Y-m-d', strtotime('2008-03-28')), date('Y-m-d', $calc->next()));
	}

	public function test15And30FebruaryLeapYearNoDD()
	{
		//$model_name, $direct_deposit, $day_of_month_1, $day_of_month_2
		$start_date = strtotime('2008-02-01');
		$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::TWICE_PER_MONTH_ON_DAYS, NULL, NULL, 15, 30);
		$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1(), FALSE), $start_date);
		$this->assertEquals(date('Y-m-d', strtotime('2008-02-19')), date('Y-m-d', $calc->current())); //don't forget presidents day!
		$this->assertEquals(date('Y-m-d', strtotime('2008-03-03')), date('Y-m-d', $calc->next()));
	}

	public function testDD()
	{
		//$model_name, $direct_deposit, $day_of_month_1, $day_of_month_2
		$start_date = strtotime('2008-02-01');
		$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::TWICE_PER_MONTH_ON_DAYS, NULL, NULL, 5, 25);
		$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1(), TRUE), $start_date);
		$this->assertEquals(date('Y-m-d', strtotime('2008-02-05')), date('Y-m-d', $calc->current())); //don't forget presidents day!
		$this->assertEquals(date('Y-m-d', strtotime('2008-02-25')), date('Y-m-d', $calc->next()));
	}
}

?>