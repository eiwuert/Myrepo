<?php

require_once('autoload_setup.php');

class Date_PayDateWeekOnDayTest extends PHPUnit_Framework_TestCase
{

		protected function setUp()
		{
		}

		public function testFridayNoDD()
		{
			$start_date = strtotime('2008-01-01');
			$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::WEEKLY_ON_DAY, 'friday');
			$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1($start_date), FALSE), $start_date);
			$this->assertEquals(date('Y-m-d', strtotime('2008-01-07')), date('Y-m-d', $calc->current()));
			$this->assertEquals(date('Y-m-d', strtotime('2008-01-14')), date('Y-m-d', $calc->next()));
		}

		public function testFridayDD()
		{
			$start_date = strtotime('2008-01-04');
			$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::WEEKLY_ON_DAY, 'friday');
			$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1($start_date), TRUE), $start_date);
			$this->assertEquals(date('Y-m-d', strtotime('2008-01-11')), date('Y-m-d', $calc->current()));
		}

		public function testHolidaysStartWrong()
		{
			$start_date = strtotime('2008-01-04');
			$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::WEEKLY_ON_DAY, 'friday');
			$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1(strtotime('2009-01-01')), TRUE), $start_date);
			$this->assertEquals(date('Y-m-d', strtotime('2008-01-11')), date('Y-m-d', $calc->current()));
		}
		
		public function testHolidayAndNextPaydate()
		{
			$start_date = strtotime('2008-01-14');
			$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::WEEKLY_ON_DAY, 'monday');
			$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1($start_date), TRUE), $start_date);
			$this->assertEquals(date('Y-m-d', strtotime('2008-01-18')), date('Y-m-d', $calc->current()));
			$this->assertEquals(date('Y-m-d', strtotime('2008-01-28')), date('Y-m-d', $calc->next()));
		}

		public function testOutOfOrder()
		{
			$start_date = strtotime('2008-01-14');
			$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::WEEKLY_ON_DAY, 'monday');
			$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1(new Date_BankHolidays_1($start_date), TRUE), $start_date);
			//skip the first element
			$this->assertEquals(date('Y-m-d', strtotime('2008-01-28')), date('Y-m-d', $calc->next()));
			$calc->rewind();
			$this->assertEquals(date('Y-m-d', strtotime('2008-01-18')), date('Y-m-d', $calc->current()));
			$this->assertEquals(date('Y-m-d', strtotime('2008-01-28')), date('Y-m-d', $calc->next()));
			//go past the previous end
			$this->assertEquals(date('Y-m-d', strtotime('2008-02-04')), date('Y-m-d', $calc->next()));
			$this->assertEquals(date('Y-m-d', strtotime('2008-02-11')), date('Y-m-d', $calc->next()));
		}


/*
$paydate_array = array($calc->current());

while(count($paydate_array) < 10)
{
	$paydate_array[] = $calc->next();
}

foreach($paydate_array as $ts)
{
	echo date("Y-m-d", $ts), PHP_EOL;
}
*/

}
?>
