<?php

class Date_PayDateEveryOtherWeekOnDayTest extends PHPUnit_Framework_TestCase
{

		protected function setUp()
		{
		}

		public function testMondayDDHoliday()
		{
			$last_paydate = strtotime('2008-01-07');
			$start_date = strtotime('2008-01-07');
			//$model_name, $direct_deposit, $day_of_week, $last_pay_date
			$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::EVERY_OTHER_WEEK_ON_DAY, 'monday', $last_paydate);
			$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1(), TRUE), $start_date);
			$this->assertEquals(date('Y-m-d', strtotime('2008-01-18')), date('Y-m-d', $calc->current()));
		}

		public function testTuesdayDD()
		{
			$last_paydate = strtotime('2008-01-08');
			$start_date = strtotime('2008-01-08');
			//$model_name, $direct_deposit, $day_of_week, $last_pay_date
			$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::EVERY_OTHER_WEEK_ON_DAY, 'tuesday', $last_paydate);
			$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1(), TRUE), $start_date);
			$this->assertEquals(date('Y-m-d', strtotime('2008-01-22')), date('Y-m-d', $calc->current()));
		}

		public function testPaidOnMondayHoliday()
		{
			$last_paydate = strtotime('2008-01-22');
			$start_date = strtotime('2008-01-20');
			//$model_name, $direct_deposit, $day_of_week, $last_pay_date
			$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::EVERY_OTHER_WEEK_ON_DAY, 'monday', $last_paydate);
			$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1(), TRUE), $start_date);
			$this->assertEquals(date('Y-m-d', strtotime('2008-02-04')), date('Y-m-d', $calc->current()));
		}

		public function testFridayNoDD()
		{
			$last_paydate = strtotime('2008-01-11');
			$start_date = strtotime('2008-01-11');
			//$model_name, $direct_deposit, $day_of_week, $last_pay_date
			$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::EVERY_OTHER_WEEK_ON_DAY, 'friday', $last_paydate);
			$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1(), FALSE), $start_date);
			$this->assertEquals(date('Y-m-d', strtotime('2008-01-28')), date('Y-m-d', $calc->current()));
		}

		public function testFridayNoDDMondayHoliday()
		{
			$last_paydate = strtotime('2008-01-04');
			$start_date = strtotime('2008-01-04');
			//$model_name, $direct_deposit, $day_of_week, $last_pay_date
			$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::EVERY_OTHER_WEEK_ON_DAY, 'friday', $last_paydate);
			$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1(), FALSE), $start_date);
			$this->assertEquals(date('Y-m-d', strtotime('2008-01-22')), date('Y-m-d', $calc->current()));
			$this->assertEquals(date('Y-m-d', strtotime('2008-02-04')), date('Y-m-d', $calc->next()));
		}

}
?>
