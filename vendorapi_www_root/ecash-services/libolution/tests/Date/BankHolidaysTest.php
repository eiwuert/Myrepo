<?php

class Date_BankHolidaysTest extends PHPUnit_Framework_TestCase
{
	public function testIterator()
	{
		$holidays = new Date_BankHolidays_1(strtotime('2007-12-31'), Date_BankHolidays_1::FORMAT_ISO);
		$this->assertEquals('2008-01-01',  $holidays->current());
		$this->assertEquals('2008-01-21',  $holidays->next());
		$this->assertEquals('2008-01-21',  $holidays->current());

	}

	public function test2008()
	{
		$holidays = new Date_BankHolidays_1(strtotime('2007-12-31'), Date_BankHolidays_1::FORMAT_ISO);
		$this->assertEquals('2008-01-01',  $holidays->current());
		$this->assertEquals('2008-01-21',  $holidays->next());
		$this->assertEquals('2008-02-18',  $holidays->next());
		$this->assertEquals('2008-05-26',  $holidays->next());
		$this->assertEquals('2008-07-04',  $holidays->next());
		$this->assertEquals('2008-09-01',  $holidays->next());
		$this->assertEquals('2008-10-13',  $holidays->next());
		$this->assertEquals('2008-11-11',  $holidays->next());
		$this->assertEquals('2008-11-27',  $holidays->next());
		$this->assertEquals('2008-12-25',  $holidays->next());
	}

	public function testSetDate()
	{
		$holidays = new Date_BankHolidays_1(strtotime('2007-12-31'), Date_BankHolidays_1::FORMAT_ISO);
		$this->assertEquals('2008-01-01',  $holidays->current());
		$this->assertEquals('2008-01-21',  $holidays->next());
		$this->assertEquals('2008-02-18',  $holidays->next());
		$this->assertEquals('2008-05-26',  $holidays->next());
		$this->assertEquals('2008-07-04',  $holidays->next());
		$this->assertEquals('2008-09-01',  $holidays->next());
		$this->assertEquals('2008-10-13',  $holidays->next());
		$this->assertEquals('2008-11-11',  $holidays->next());
		$this->assertEquals('2008-11-27',  $holidays->next());
		$this->assertEquals('2008-12-25',  $holidays->next());
		$holidays->setStartDate(strtotime('2006-12-31'));
		$this->assertEquals('2007-01-01',  $holidays->current());
		$this->assertEquals('2007-01-15',  $holidays->next());
		$this->assertEquals('2007-02-19',  $holidays->next());
		$this->assertEquals('2007-05-28',  $holidays->next());
		$this->assertEquals('2007-07-04',  $holidays->next());
		$this->assertEquals('2007-09-03',  $holidays->next());
		$this->assertEquals('2007-10-08',  $holidays->next());
		$this->assertEquals('2007-11-12',  $holidays->next());
		$this->assertEquals('2007-11-22',  $holidays->next());
		$this->assertEquals('2007-12-25',  $holidays->next());
	}

	public function testSetDate2()
	{
		$holidays = new Date_BankHolidays_1(strtotime('2009-01-01'), Date_BankHolidays_1::FORMAT_ISO);
		$holidays->setStartDate(strtotime('2008-01-04'));
		$this->assertEquals('2008-01-21',  $holidays->current());
	}

	public function testConstructorNormalizesToMidnight()
	{
		$holidays = new Date_BankHolidays_1(strtotime('2008-01-01 00:00:01'), Date_BankHolidays_1::FORMAT_ISO);
		$this->assertEquals('2008-01-01',  $holidays->current());
		$this->assertEquals('2008-01-21',  $holidays->next());
	}

	public function testSetStartDateNormalizesToMidnight()
	{
		$holidays = new Date_BankHolidays_1(strtotime('2008-03-01'), Date_BankHolidays_1::FORMAT_ISO);
		$holidays->setStartDate(strtotime('2008-01-01 23:59:59'));
		$this->assertEquals('2008-01-01',  $holidays->current());
		$this->assertEquals('2008-01-21',  $holidays->next());
	}

	public function testDynamic()
	{
		$holidays = new Date_BankHolidays_1();
		$holidays->setFormat(Date_BankHolidays_1::FORMAT_TIMESTAMP);
		$this->assertNotEquals(0,  $holidays->current());
		/**
		 * @TODO CHANGE ME! (then uncomment assert and run)
		 */
		$next_holiday = '2008-02-18';
		//$this->assertEquals($next_holiday,  date('Y-m-d', $holidays->current()));
	}
}

?>
