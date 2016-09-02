<?php

class Date_PayDateMonthlyOnDayTest extends PHPUnit_Framework_TestCase
{
	public function test5thDD()
	{
		//$model_name, $direct_deposit, $day_of_month_1
		$start_date = strtotime('2008-02-01');
		$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::MONTHLY_ON_DAY, NULL, NULL, 5);
		$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1(), TRUE), $start_date);
		$this->assertEquals(date('Y-m-d', strtotime('2008-02-05')), date('Y-m-d', $calc->current()));
		$this->assertEquals(date('Y-m-d', strtotime('2008-03-05')), date('Y-m-d', $calc->next())); //don't forget president's day!
		$this->assertEquals(date('Y-m-d', strtotime('2008-04-04')), date('Y-m-d', $calc->next())); //don't forget president's day!
	}
}

?>