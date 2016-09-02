<?php
/**
 * OperatingHoursTest PHPUnit test file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Rule_OperatingHours class.
 *
 * @group rules
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Rule_OperatingHoursTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Setup function for each test.
	 * 
	 * @return void
	 *
	 * @todo Fix cruise control to use olp_lib then take off test skipping
	 */
	public function setUp()
	{
		if (!file_exists('/virtualhosts/olp_lib/OperatingHours.php'))
		{
			$this->markTestSkipped("olp_lib isnt setup so OperatingHours.php is broken");
		}
		$this->utils = new Blackbox_Utils();
	}
	
	/**
	 * Tear Down function for each test.
	 * 
	 * @return void
	 */
	public function tearDown()
	{
		$this->utils->resetToday();
	}
	
	/**
	 * Data provider for cases that should return true.
	 *
	 * @return array
	 *
	 * @todo Fix cruise control to use olp_lib
	 * @todo add olp_lib to include path and remove full path
	 */
	public static function dataProvider()
	{
		if (!file_exists('/virtualhosts/olp_lib/OperatingHours.php'))
		{
			return TRUE;
		}
		include_once('/virtualhosts/olp_lib/OperatingHours.php');
		
		$operating_hours = new OperatingHours();
		$operating_hours->addDayOfWeekHours('mon', '8:00', '16:00');
		$operating_hours->addDayOfWeekHours('tue', '8:00', '11:00');
		$operating_hours->addDayOfWeekHours('tue', '12:00', '16:00');
		$operating_hours->addDateHours('2008-02-25', '12:00', '14:00');
		
		// Expected Return, Operating Hours, Date/Time
		return array(
			// Test a day with a single set of hours.
			array(FALSE, $operating_hours, '2008-02-18 7:59'), // Monday before start time, fail
			array(FALSE, $operating_hours, '2008-02-18 8:00'), // Monday on start time, fail
			array(TRUE, $operating_hours, '2008-02-18 8:00:01'), // Monday after start time, pass
			array(TRUE, $operating_hours, '2008-02-18 15:59:59'), // Monday before end time, pass
			array(FALSE, $operating_hours, '2008-02-18 16:00'), // Monday on end time, fail
			array(FALSE, $operating_hours, '2008-02-18 16:01'), // Monday after end time, fail
			
			// Test a day with multiple sets of operating hours.			
			array(FALSE, $operating_hours, '2008-02-19 7:59'), // Tuesday before first start time, fail
			array(FALSE, $operating_hours, '2008-02-19 8:00'), // Tuesday on first start time, fail
			array(TRUE, $operating_hours, '2008-02-19 8:00:01'), // Tuesday after first start time, pass
			array(TRUE, $operating_hours, '2008-02-19 10:59:59'), // Tuesday before first end time, pass
			array(FALSE, $operating_hours, '2008-02-19 11:00'), // Tuesday on first end time, fail
			array(FALSE, $operating_hours, '2008-02-19 11:01'), // Tuesday after first end time, fail
			array(FALSE, $operating_hours, '2008-02-19 11:59'), // Tuesday before second start time, fail
			array(FALSE, $operating_hours, '2008-02-19 12:00'), // Tuesday on second start time, fail
			array(TRUE, $operating_hours, '2008-02-19 12:00:01'), // Tuesday after second start time, pass
			array(TRUE, $operating_hours, '2008-02-19 13:59:59'), // Tuesday before second end time, pass
			array(FALSE, $operating_hours, '2008-02-19 16:00'), // Tuesday on second end time, fail
			array(FALSE, $operating_hours, '2008-02-19 16:01'), // Tuesday after second end time, fail
			
			// Test a special date
			array(FALSE, $operating_hours, '2008-02-25 11:59'), // Special date before start time, fail
			array(FALSE, $operating_hours, '2008-02-25 12:00'), // Special date on start time, fail
			array(TRUE, $operating_hours, '2008-02-25 12:00:01'), // Special date after start time, pass
			array(TRUE, $operating_hours, '2008-02-25 13:59:59'), // Special date before end time, pass
			array(FALSE, $operating_hours, '2008-02-25 14:00'), // Special date on end time, fail
			array(FALSE, $operating_hours, '2008-02-25 14:01'), // Special date after end time, fail
			
			// Test a day with no operating hours set, it should pass.
			array(TRUE, $operating_hours, '2008-02-17 8:00'), // Sunday that has no operating hours set, pass
		);
	}
	
	/**
	 * Test all of the day of week cases from our data provider.
	 *
	 * @param bool $expected The expected result of the test
	 * @param OperatingHours $operating_hours The operating hours object to test against
	 * @param string $date_time The date to test
	 *
	 * @return void
	 *
	 * @dataProvider dataProvider
	 */
	public function testOperatingHours($expected, $operating_hours, $date_time)
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();
		
		$this->utils->setToday($date_time);
		
		$rule = new OLPBlackbox_Rule_OperatingHours($operating_hours);
		$rule = $this->getMock(
			'OLPBlackbox_Rule_OperatingHours',
			array('hitStat', 'hitEvent'),
			array($operating_hours)
		);
		$v = $rule->isValid($data, $state_data);
		
		if ($expected)
		{
			$this->assertTrue($v);
		}
		else
		{
			$this->assertFalse($v);
		}
	}
}
?>
