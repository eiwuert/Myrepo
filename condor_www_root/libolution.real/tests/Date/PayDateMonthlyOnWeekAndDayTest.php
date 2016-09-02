<?php

require_once('autoload_setup.php');

class Date_PayDateMonthlyOnWeekAndDayTest extends PHPUnit_Framework_TestCase
{
	public function test1stFridayDD()
	{
		//$model_name, $direct_deposit, $week_1, $day_of_week
		$start_date = strtotime('2008-02-01');
		$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::MONTHLY_ON_WEEK_AND_DAY, 'friday', NULL, NULL, NULL, 1);
		$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1(), TRUE), $start_date);
		$this->assertEquals(date('Y-m-d', strtotime('2008-03-07')), date('Y-m-d', $calc->current()));
		$this->assertEquals(date('Y-m-d', strtotime('2008-04-04')), date('Y-m-d', $calc->next()));
		$this->assertEquals(date('Y-m-d', strtotime('2008-05-02')), date('Y-m-d', $calc->next()));
	}

	public function test1stFridayNoDD()
	{
		//$model_name, $direct_deposit, $week_1, $day_of_week
		$start_date = strtotime('2008-02-01');
		$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::MONTHLY_ON_WEEK_AND_DAY, 'friday', NULL, NULL, NULL, 1);
		$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1(), FALSE), $start_date);
		//double check this first one, the normalized paydate is after the 1st
		$this->assertEquals(date('Y-m-d', strtotime('2008-02-04')), date('Y-m-d', $calc->current()));
		$this->assertEquals(date('Y-m-d', strtotime('2008-03-10')), date('Y-m-d', $calc->next()));
		$this->assertEquals(date('Y-m-d', strtotime('2008-04-07')), date('Y-m-d', $calc->next()));
	}
}

?>