<?php
require_once 'pay_date_calc.3.php';

/**
 * Tests the ECash_Qualify class for eCash Commercial.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class ECash_QualifyTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testGetGracePeriodDate.
	 *
	 * @return array
	 */
	public static function dataProviderTestGetGracePeriodDate()
	{
		return array(
			array(array(), time(), strtotime('+10 days')),
			array(array('grace_period' => 5), time(), strtotime('+5 days'))
		);
	}
	
	/**
	 * Tests the getGracePeriodDate method.
	 *
	 * @dataProvider dataProviderTestGetGracePeriodDate
	 * @param int $date
	 * @param int $expected
	 * @return void
	 */
	public function testGetGracePeriodDate($rules, $date, $expected)
	{
		$qualify = new ECash_Qualify($rules, $this->getMock('Pay_Date_Calc_3'));
		$this->assertEquals($expected, $qualify->getGracePeriodDate($date));
	}
	
	/**
	 * Data provider for testCalculateDueDateDirectDeposit
	 *
	 * @return array
	 */
	public static function dataProviderTestCalculateDueDate()
	{
		return array(
			array(TRUE, strtotime('2009-01-26 04:00:00')),
			array(FALSE, strtotime('2009-01-26 04:00:00'))
		);
	}
	
	/**
	 * Tests the calculateDueDate method.
	 * 
	 * This test originally tested that we got back different dates for calculateDueDate depending on direct deposit.
	 * Qualify now expects that the paydates passed to it are already adjusted for direct deposit, so this tests that
	 * the date DOESN'T change now.
	 *
	 * @dataProvider dataProviderTestCalculateDueDate
	 * @param unknown_type $direct_deposit
	 * @param unknown_type $expected_date
	 * @return void
	 */
	public function testCalculateDueDateDirectDeposit($direct_deposit, $expected_date)
	{
		$pay_dates = array(strtotime('2009-01-26'));
		
		$qualify = $this->getMock(
			'ECash_Qualify',
			array('getNextValidDay', 'checkGracePeriod'),
			array(array(), $this->getMock('Pay_Date_Calc_3'))
		);
		
		$qualify->expects($this->any())
			->method('getNextValidDay')
			->will($this->returnArgument(0));
			
		$qualify->expects($this->any())
			->method('checkGracePeriod')
			->will($this->returnValue(TRUE));
		
		$due_date = $qualify->calculateDueDate($pay_dates, $direct_deposit, NULL);
		
		$this->assertEquals(
			date('c', $expected_date),
			date('c', $due_date)
		);
	}
	
	/**
	 * Tests the calculateDueDate foreach loop to see that we actually come around and get the correct date.
	 *
	 * @return void
	 */
	public function testCalculateDueDateForeachLoop()
	{
		$pay_dates = array(strtotime('2009-01-26'), strtotime('2009-02-02'));
		
		$qualify = $this->getMock(
			'ECash_Qualify',
			array('getNextValidDay', 'checkGracePeriod'),
			array(array(), $this->getMock('Pay_Date_Calc_3'))
		);
		
		$qualify->expects($this->any())
			->method('getNextValidDay')
			->will($this->returnArgument(0));
			
		$qualify->expects($this->any())
			->method('checkGracePeriod')
			->will($this->onConsecutiveCalls(FALSE, TRUE));
		
		$due_date = $qualify->calculateDueDate($pay_dates, TRUE, NULL);
		$this->assertEquals(
			date('c', strtotime('2009-02-02 04:00:00')),
			date('c', $due_date)
		);
	}
	
	/**
	 * Data provider for the getNextValidDay tests.
	 *
	 * @return array
	 */
	public static function dataProviderTestGetNextValidDay()
	{
		return array(
			array(TRUE, strtotime('2009-01-25 04:00:00')),
			array(FALSE, strtotime('2009-01-27 04:00:00'))
		);
	}
	
	/**
	 * Test getNextValidDay with a weekend match.
	 *
	 * @dataProvider dataProviderTestGetNextValidDay
	 * @param unknown_type $direct_deposit
	 * @return void
	 */
	public function testGetNextValidDayWeekend($direct_deposit, $expected)
	{
		$pay_dates = array(strtotime('2009-01-26'));
		
		$calc = $this->getMock('Pay_Date_Calc_3');
		
		$calc->expects($this->any())
			->method('Is_Weekend')
			->will($this->onConsecutiveCalls(TRUE, FALSE));
			
		$calc->expects($this->any())
			->method('Is_Holiday')
			->will($this->returnValue(FALSE));
		
		$qualify = $this->getMock(
			'ECash_Qualify',
			array('checkGracePeriod'),
			array(array(), $calc)
		);
		
		$qualify->expects($this->any())
			->method('checkGracePeriod')
			->will($this->returnValue(TRUE));
		
		$due_date = $qualify->calculateDueDate($pay_dates, $direct_deposit, NULL);
		
		$this->assertEquals(
			date('c', $expected),
			date('c', $due_date)
		);
	}
	
	/**
	 * Tests getNextValidDay with a holiday match.
	 *
	 * @dataProvider dataProviderTestGetNextValidDay
	 * @param unknown_type $direct_deposit
	 * @return void
	 */
	public function testGetNextValidDayHoliday($direct_deposit, $expected)
	{
		$pay_dates = array(strtotime('2009-01-26'));
		
		$calc = $this->getMock('Pay_Date_Calc_3');
		
		$calc->expects($this->any())
			->method('Is_Holiday')
			->will($this->onConsecutiveCalls(TRUE, FALSE));
			
		$calc->expects($this->any())
			->method('Is_Weekend')
			->will($this->returnValue(FALSE));
		
		$qualify = $this->getMock(
			'ECash_Qualify',
			array('checkGracePeriod'),
			array(array(), $calc)
		);
		
		$qualify->expects($this->any())
			->method('checkGracePeriod')
			->will($this->returnValue(TRUE));
		
		$due_date = $qualify->calculateDueDate($pay_dates, $direct_deposit, NULL);
		
		$this->assertEquals(
			date('c', $expected),
			date('c', $due_date)
		);
	}
	
	/**
	 * Tests that we get the correct due date back with the grace period.
	 *
	 * @return void
	 */
	public function testCheckGracePeriod()
	{
		$pay_dates = array(
			strtotime('2009-01-26'),
			strtotime('2009-02-02'),
			strtotime('2009-02-09')
		);
		
		$qualify = $this->getMock(
			'ECash_Qualify',
			array('getNextValidDay', 'getGracePeriodDate'),
			array(array(), $this->getMock('Pay_Date_Calc_3'))
		);
		
		$qualify->expects($this->any())
			->method('getNextValidDay')
			->will($this->returnArgument(0));
			
		$qualify->expects($this->any())
			->method('getGracePeriodDate')
			->will($this->returnValue(strtotime('+10 days', strtotime('2009-01-28'))));
		
		$due_date = $qualify->calculateDueDate($pay_dates, TRUE, NULL);
		
		$this->assertEquals(
			date('c', strtotime('2009-02-09 04:00:00')),
			date('c', $due_date)
		);
	}
}
