<?php
/**
 * OLPBlackbox_OperatingHours test case.
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPBlackbox_OperatingHoursTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * OperatingHurs class for tests
	 * 
	 * @var OperatingHours
	 */
	protected $operating_hours;
	
	protected $date_array;
	
	protected $day_array;
	
	protected $import_array;
	
	/**
	 * Prepares the environment before running a test.
	 * 
	 * @return void
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->operating_hours = new OLPBlackbox_OperatingHours();
		
		$this->day_array = array(
			array(
				'day' => array('start' => OLPBlackbox_OperatingHours::MON, 'end' => OLPBlackbox_OperatingHours::MON),
				'time' => array('start' => '00:00', 'end' => '00:01'),
			),
			array(
				'day' => array('start' => OLPBlackbox_OperatingHours::TUE, 'end' => OLPBlackbox_OperatingHours::TUE),
				'time' => array('start' => '00:01', 'end' => '00:02'),
			),
			array(
				'day' => array('start' => OLPBlackbox_OperatingHours::TUE, 'end' => OLPBlackbox_OperatingHours::TUE),
				'time' => array('start' => '00:02', 'end' => '00:03'),
			),
		);
		
		$this->date_array = array(
			array(
				'date' => array('start' => '2009-01-01', 'end' => '2009-01-01'),
				'time' => array('start' => '00:03', 'end' => '00:04'),
			),
			array(
				'date' => array('start' => '2009-01-01', 'end' => '2009-01-01'),
				'time' => array('start' => '00:04', 'end' => '00:05'),
			),
			array(
				'date' => array('start' => '2009-02-02', 'end' => '2009-02-02'),
				'time' => array('start' => '00:05', 'end' => '00:06')
			),
		);
		
		$this->import_array = array(
			'days' => $this->day_array,
			'dates' => $this->date_array
		);
		
	}
	
	/**
	 * Cleans up the environment after running a test.
	 * 
	 * @return void
	 */
	protected function tearDown()
	{
		$this->operating_hours = NULL;
		parent::tearDown();
	}
	
	/**
	 * Tests OperatingHours->addDateHours()
	 * 
	 * @return void
	 */
	public function testAddDateHours()
	{
		// Test adding a dead day
		$this->operating_hours->addDateHours('2009-01-01', '2009-01-01', '00:03', '00:04');
		$this->operating_hours->addDateHours('January 1, 2009', 'January 1, 2009', '00:04', '00:05');
		$this->operating_hours->addDateHours('Feb 2, 2009', 'Feb 2, 2009', '00:05', '00:06');
		
		// End time less than start time will throw invalid argument exception
		try
		{
			$this->operating_hours->addDateHours('2009-01-01', '2009-01-01', '01:00', '00:00');
			$this->fail('Expected InvalidArgumentException exception and none thrown');
		}
		catch (InvalidArgumentException $e)
		{
			// Intentionally blank
		}
		
		
		// Bad date will throw invalid argument exception
		try
		{
			$this->operating_hours->addDateHours('05052008', '05052008', '00:00', '00:00');
			$this->fail('Expected InvalidArgumentException exception and none thrown');
		}
		catch (InvalidArgumentException $e)
		{
			// Intentionally blank
		}
		
		$this->assertAttributeEquals($this->date_array, 'date_hours', $this->operating_hours);
		
	}
	
	/**
	 * Tests OperatingHours->addDayOfWeekHours()
	 * 
	 * @return void
	 */
	public function testAddDayOfWeekHours()
	{
		// Test adding a dead day
		$this->operating_hours->addDayOfWeekHours(OLPBlackbox_OperatingHours::MON, OLPBlackbox_OperatingHours::MON, '00:00', '00:01');
		$this->operating_hours->addDayOfWeekHours(OLPBlackbox_OperatingHours::TUE, OLPBlackbox_OperatingHours::TUE, '00:01', '00:02');
		$this->operating_hours->addDayOfWeekHours(OLPBlackbox_OperatingHours::TUE, OLPBlackbox_OperatingHours::TUE, '00:02', '00:03');
		
		// End time less than start time will throw invalid argument exception
		try
		{
			$this->operating_hours->addDayOfWeekHours(OLPBlackbox_OperatingHours::MON, OLPBlackbox_OperatingHours::MON, '01:00', '00:00');
			$this->fail('Expected InvalidArgumentException exception and none thrown');
		}
		catch (InvalidArgumentException $e)
		{
			// Intentionally blank
		}
		
		
		// Bad day will throw invalid argument exception
		try
		{
			$this->operating_hours->addDayOfWeekHours('Blevensday', 'Blevensday', '00:00', '00:00');
			$this->fail('Expected InvalidArgumentException exception and none thrown');
		}
		catch (InvalidArgumentException $e)
		{
			// Intentionally blank
		}
		
		$this->assertAttributeEquals($this->day_array, 'day_hours', $this->operating_hours);
		
	}

	/**
	 * testDateIsValid data provider
	 *
	 * @return array
	 */
	public function providerDateIsValid()
	{
		return array(
			array('5-10-2000', TRUE),
			array('2000-02-31', TRUE),
			array('2/29/2000', TRUE),
			array('20010229', TRUE),
			array('blevins day', FALSE),
			array('1/1/1900', (PHP_INT_SIZE > 4)), // Only valid if using larger than 32bit ints
		);
	}
	
	/**
	 * Tests OperatingHours::dateIsValid()
	 *
	 * @param string $date
	 * @param bool $success
	 * @return void
	 * 
	 * @dataProvider providerDateIsValid
	 */
	public function testDateIsValid($date, $success)
	{
		$this->assertEquals($success, OLPBlackbox_OperatingHours::dateIsValid($date));
	}

	/**
	 * testDayIsValid data provider
	 *
	 * @return array
	 */
	public function providerDayIsValid()
	{
		return array(
			array(OLPBlackbox_OperatingHours::FRI, TRUE),
			array(OLPBlackbox_OperatingHours::MON, TRUE),
			array('Blue', FALSE),
			array(1, FALSE),
			array('Monday', FALSE),
			array('mon', FALSE),
		);
	}
	
	/**
	 * Tests OperatingHours::dayIsValid()
	 * 
	 * @param string $day
	 * @param bool $success
	 * @return void
	 * 
	 * @dataProvider providerDayIsValid
	 */
	public function testDayIsValid($day, $success)
	{
		$this->assertEquals($success, OLPBlackbox_OperatingHours::dayIsValid($day));
	}

	/**
	 * Data Provider for testFromOldArray()
	 *
	 * @return array
	 */
	public function providerFromOldArrayVaildInput()
	{
		return array(
			array(array('date' => array('2009-01-01' => array(array('start' => '00:00', 'end' => '00:00')))),TRUE),
			array(array('date' => array('2009-01-01' => array(array('start' => '00:01', 'end' => '00:00')))), FALSE), // Valid date bad time
			array(array('date' => array('2009-01-01' => array(array('start' => '00:00', 'end' => '24:00')))), FALSE), // Valid date bad time
			array(array('date' => array('2009-01-01' => array(array('start' => '00:00', 'end' => '00:60')))), FALSE), // Valid date bad time
			array(array('date' => array('2009-01-01' => array(array('start' => '00:00')))), FALSE), // Valid date bad time
			array(array('date' => array('2009-01-01' => array(array('end' => '00:00')))), FALSE), // Valid date bad time
			array(array('date' => array('2009-01-01' => array(array('start', 'end')))), FALSE), // Valid date bad time
			array(array('day_of_week' => array(OLPBlackbox_OperatingHours::MON => array(array('start' => '00:00', 'end' => '00:00')))), TRUE), // Valid day
			array(array('day_of_week' => array(OLPBlackbox_OperatingHours::MON => array(array('start' => '00:00', 'end' => '00:00')))), TRUE), // Valid date
			array(array('day_of_week' => array(OLPBlackbox_OperatingHours::MON => array(array('start' => '00:01', 'end' => '00:00')))), FALSE), // Valid date bad time
			array(array('day_of_week' => array(OLPBlackbox_OperatingHours::MON => array(array('start' => '00:00', 'end' => '24:00')))), FALSE), // Valid date bad time
			array(array('day_of_week' => array(OLPBlackbox_OperatingHours::MON => array(array('start' => '00:00', 'end' => '00:60')))), FALSE), // Valid date bad time
			array(array('day_of_week' => array(OLPBlackbox_OperatingHours::MON => array(array('start' => '00:00')))), FALSE), // Valid date bad time
			array(array('day_of_week' => array(OLPBlackbox_OperatingHours::MON => array(array('end' => '00:00')))), FALSE), // Valid date bad time
			array(array('day_of_week' => array(OLPBlackbox_OperatingHours::MON => array(array('start', 'end')))), FALSE), // Valid date bad time
			array(array('day_of_week' => array()), TRUE),
		);
	}
	
	/**
	 * Tests OperatingHours->fromOldArray() valid input types
	 * 
	 * @return void
	 * @dataProvider providerFromOldArrayVaildInput
	 */
	public function testFromArrayOldVaildInput($array, $valid)
	{
		// Test array configurations that throw exceptions
		try
		{
			$this->operating_hours->fromArray($array);
			if (!$valid) $this->fail('Exception was not thrown when expected');
		}
		catch (InvalidArgumentException $e)
		{
			if ($valid) $this->fail('Exception was thrown when none expected');
		}
	}

	/**
	 * Data provider for testFromOldArrayDayRangeConversion
	 *
	 * @return array
	 */
	public function providerFromOldArrayDayRangeConversion()
	{
		return array(
			array('Mon', 'Mon', 'Mon'),
			array('Sun', 'Sun', 'Sun'),
			array('WkDays', 'Mon', 'Fri'),
			array('WkEnd', 'Sat', 'Sun'),
			array('WkAll', 'Mon', 'Sun'),
		);
	}

	/**
	 * Verify that the legacy range identifiers are properly converted into ranges in fromArray
	 * by checking what values with which addDayOfWeekHours() is called
	 *
	 * @param string $day day provided in import
	 * @param unknown_type $expected_start Expected value to use as start day for range
	 * @param unknown_type $expected_end Expected value to use as end day for range
	 * @return void
	 * @dataProvider providerFromOldArrayDayRangeConversion
	 */
	public function testFromOldArrayDayRangeConversion($day, $expected_start, $expected_end)
	{
		$start_time = '00:00';
		$end_time = '00:01';
		$import = array(
			'day_of_week' => array($day => array(array('start' => $start_time, 'end' => $end_time)))
		);
		
		$operating_hours = $this->getMock('OLPBlackbox_OperatingHours', array('addDayOfWeekHours'));
		$operating_hours
			->expects($this->once())
			->method('addDayOfWeekHours')
			->with($expected_start, $expected_end, $start_time, $end_time);
		
		$operating_hours->fromArray($import);
	}

	/**
	 * Tests OperatingHours->fromArray()
	 * 
	 * @return array
	 * @expectedException InvalidArgumentException
	 */
	public function providerFromArray()
	{
		return array(
			// All good
			array(
				array(
					'days' => array(
						array(
							'day' => array('start' => 'Mon', 'end' => 'Mon'),
							'time' => array('start' => '01:00', 'end' => '01:00')
						),
						array(
							'day' => array('start' => 'Mon', 'end' => 'Fri'),
							'time' => array('start' => '01:00', 'end' => '02:00')
						),
						array(
							'day' => array('start' => 'Thu', 'end' => 'Wed'),
							'time' => array('start' => '02:00', 'end' => '03:00')
						),
					),
					'dates' => array(
						array(
							'date' => array('start' => '2009-01-01', 'end' => '2009-01-01'),
							'time' => array('start' => '01:00', 'end' => '02:00')
						),
						array(
							'date' => array('start' => '2009-01-01', 'end' => '2009-01-01'),
							'time' => array('start' => '02:00', 'end' => '03:00')
						),
						array(
							'date' => array('start' => '2009-01-01', 'end' => '2009-01-07'),
							'time' => array('start' => '01:00', 'end' => '01:00')
						),
					)
				),
				FALSE
			),
			// Bad Date item
			array(
				array(
					'dates' => array(
						array(
							'date' => array('start' => 'Blue', 'end' => '2009-01-01'),
							'time' => array('start' => '01:00', 'end' => '01:00')
						),
					)
				),
				TRUE
			),
			// Bad Day item
			array(
				array(
					'days' => array(
						array(
							'day' => array('start' => 'Blue', 'end' => 'Mon'),
							'time' => array('start' => '01:00', 'end' => '01:00')
						)
					),
				),
				TRUE
			),
			// Bad Date Range item
			array(
				array(
					'dates' => array(
						array(
							'date' => array('start' => '2009-01-01', 'end' => '2008-01-01'),
							'time' => array('start' => '01:00', 'end' => '01:00')
						)
					)
				),
				TRUE
			),
		);
	}
	
	/**
	 * Tests OperatingHours->fromArray()
	 * 
	 * @return void
	 * @dataProvider providerFromArray
	 */
	public function testFromArray($array, $throws_exception)
	{
		if ($throws_exception) $this->setExpectedException('InvalidArgumentException');
		$this->operating_hours->fromArray($array);
		if (isset($array['dates'])) $this->assertAttributeEquals($array['dates'], 'date_hours', $this->operating_hours);
		if (isset($array['days'])) $this->assertAttributeEquals($array['days'], 'day_hours', $this->operating_hours);
	}

	/**
	 * Tests OperatingHours->testToArray()
	 * 
	 * @return void
	 */
	public function testToArray()
	{
		$this->operating_hours->fromArray($this->import_array);
		$this->assertEquals($this->import_array, $this->operating_hours->toArray());
	
	}
	
	/**
	 * Tests OperatingHours::getValidDays()
	 * 
	 * @return void
	 */
	public function testGetValidDays()
	{
		$this->assertType('array', OLPBlackbox_OperatingHours::getValidDays());	
	}

	/**
	 * Data provider for testTimeIsValid()
	 *
	 * @return array
	 */
	public function providerTimeIsValid()
	{
		return array(
			array('efg', FALSE),
			array('01:01', TRUE),
			array('0101', FALSE),
			array('13:01', TRUE),
			array(123, FALSE),
			array('24:00', FALSE),
			array('00:60', FALSE),
		);	
	}
	
	/**
	 * Tests OperatingHours::timeIsValid()
	 * 
	 * @param string $time
	 * @return void
	 * @dataProvider providerTimeIsValid
	 */
	public function testTimeIsValid($time, $valid)
	{
		$this->assertEquals($valid, OLPBlackbox_OperatingHours::timeIsValid($time));
	}
	
	/**
	 * Data provider for timesAreValid()
	 *
	 * @return array
	 */
	public function providerTimesAreValid()
	{
		return array(
			array('00:00', '00:00', TRUE),
			array('00:01', '00:02', TRUE),
			array('00:02', '00:01', FALSE),
			array('00:00', '24:00', FALSE),
			array('00:00', '00:60', FALSE),
			array(123, NULL, FALSE),
		);	
	}
	
	/**
	 * Tests OperatingHours::timesAreValid()
	 *
	 * @param string $start
	 * @param string $end
	 * @param bool $valid
	 * @return void
	 * @dataProvider providerTimesAreValid
	 */
	public function testTimesAreValid($start, $end, $valid)
	{
		$this->assertEquals($valid, OLPBlackbox_OperatingHours::timesAreValid($start, $end));
	
	}

	public function providerIsDayBetweenDays()
	{
		return array(
			array('Wed', 'Wed', 'Wed', TRUE),
			array('Wed', 'Tue', 'Sun', TRUE),
			array('Wed', 'Thu', 'Sat', FALSE),
			array('Wed', 'Thu', 'Tue', FALSE),
			array('Wed', 'Sun', 'Sun', FALSE),
			array('Sun', 'Mon', 'Sat', FALSE),
		);	
	}

	/**
	 * Test the isDayBwetweenDays validation function 
	 *
	 * @param string $day
	 * @param string $start
	 * @param string $end
	 * @param bool $in_range
	 * @return void
	 * @dataProvider providerIsDayBetweenDays
	 */
	public function testIsDayBwetweenDays($day, $start, $end, $in_range)
	{
		$this->assertEquals($in_range, OLPBlackbox_OperatingHours::isDayBetweenDays($day, $start, $end));
	}

	/**
	 * Data provider for testIsOpen()
	 *
	 * @return void
	 */
	public function providerIsOpen()
	{
		return array(
			// Single Day closed override
			array(
				array(
					'days' => array(
						array(
							'day' => array('start' => 'Mon', 'end' => 'Mon'),
							'time' => array('start' => '01:00', 'end' => '01:00')
						),
						array(
							'day' => array('start' => 'Thu', 'end' => 'Wed'),
							'time' => array('start' => '02:00', 'end' => '03:00')
						),
					),
				),
				'Monday 12:00',
				FALSE
			),
			// Day Range closed override
			array(
				array(
					'days' => array(
						array(
							'day' => array('start' => 'Mon', 'end' => 'Wed'),
							'time' => array('start' => '01:00', 'end' => '01:00')
						),
						array(
							'day' => array('start' => 'Thu', 'end' => 'Wed'),
							'time' => array('start' => '02:00', 'end' => '03:00')
						),
					),
				),
				'Tuesday 12:00',
				FALSE
			),
			// Date closed override
			array(
				array(
					'dates' => array(
						array(
							'date' => array('start' => '2009-01-01', 'end' => '2009-01-01'),
							'time' => array('start' => '01:00', 'end' => '01:00')
						),
						array(
							'date' => array('start' => '2008-12-01', 'end' => '2009-02-01'),
							'time' => array('start' => '01:00', 'end' => '18:00')
						),
						array(
							'date' => array('start' => '2006-01-01', 'end' => '2009-01-01'),
							'time' => array('start' => '01:00', 'end' => '03:00')
						),
					)
				),
				'2009-01-01',
				FALSE
			),
			// Date range closed override
			array(
				array(
					'dates' => array(
						array(
							'date' => array('start' => '2009-01-01', 'end' => '2009-05-01'),
							'time' => array('start' => '01:00', 'end' => '01:00')
						),
						array(
							'date' => array('start' => '2008-12-01', 'end' => '2009-02-01'),
							'time' => array('start' => '01:00', 'end' => '18:00')
						),
						array(
							'date' => array('start' => '2006-01-01', 'end' => '2009-01-01'),
							'time' => array('start' => '01:00', 'end' => '03:00')
						),
					)
				),
				'2009-03-01',
				FALSE
			),
			// Date override over valid day
			array(
				array(
					'dates' => array(
						array(
							'date' => array('start' => '2009-01-01', 'end' => '2009-05-01'),
							'time' => array('start' => '01:00', 'end' => '01:00')
						),
						array(
							'date' => array('start' => '2008-12-01', 'end' => '2009-02-01'),
							'time' => array('start' => '01:00', 'end' => '18:00')
						),
						array(
							'date' => array('start' => '2006-01-01', 'end' => '2009-01-01'),
							'time' => array('start' => '01:00', 'end' => '03:00')
						),
					)
				),
				'2001-01-01',
				FALSE
			),
			// No days in range
			array(
				array(
					'days' => array(
						array(
							'day' => array('start' => 'Mon', 'end' => 'Sat'),
							'time' => array('start' => '00:00', 'end' => '23:59')
						),
						array(
							'day' => array('start' => 'Sun', 'end' => 'Sun'),
							'time' => array('start' => '12:01', 'end' => '23:59')
						),
					),
				),
				'Sun 12:00',
				FALSE
			),
			// Valid day
			array(
				array(
					'days' => array(
						array(
							'day' => array('start' => 'Mon', 'end' => 'Sat'),
							'time' => array('start' => '00:00', 'end' => '23:59')
						),
						array(
							'day' => array('start' => 'Sun', 'end' => 'Sun'),
							'time' => array('start' => '12:01', 'end' => '23:59')
						),
					),
				),
				'Mon 12:00',
				TRUE
			),
			// Date value overrides day value
			array(
				array(
					'days' => array(
						array(
							'day' => array('start' => date('D', strtotime('2009-06-22')), 'end' =>  date('D', strtotime('2009-06-22'))),
							'time' => array('start' => '00:00', 'end' => '00:00')
						),
					),
					'dates' => array(
						array(
							'date' => array('start' => '2009-06-22', 'end' => '2009-06-22'),
							'time' => array('start' => '17:00', 'end' => '19:00')
						),
					)
				),
				'2009-06-22 18:00',
				TRUE
			),
			// Day closed value overrides other day values
			array(
				array(
					'days' => array(
						array(
							'day' => array('start' => date('D', strtotime('2009-06-22')), 'end' =>  date('D', strtotime('2009-06-22'))),
							'time' => array('start' => '00:00', 'end' => '00:00')
						),
						array(
							'day' => array('start' => date('D', strtotime('2009-06-22')), 'end' =>  date('D', strtotime('2009-06-22'))),
							'time' => array('start' => '00:00', 'end' => '23:59')
						),
					),
				),
				'2009-06-22 18:00',
				FALSE
			),
			// Date closed value overrides day and date value
			array(
				array(
					'days' => array(
						array(
							'day' => array('start' => date('D', strtotime('2009-06-22')), 'end' =>  date('D', strtotime('2009-06-22'))),
							'time' => array('start' => '00:00', 'end' => '23:59')
						),
					),
					'dates' => array(
						array(
							'date' => array('start' => '2009-01-22', 'end' => '2009-12-22'),
							'time' => array('start' => '00:00', 'end' => '23:59')
						),
						array(
							'date' => array('start' => '2009-06-22', 'end' => '2009-06-22'),
							'time' => array('start' => '00:00', 'end' => '00:00')
						),
					)
				),
				'2009-06-22 18:00',
				FALSE
			),
		);
	}

	/**
	 * Test the isOpen functionality
	 *
	 * @param array $import_array
	 * @param string $date_time
	 * @param bool $open
	 * @return void
	 * @dataProvider providerIsOpen
	 */
	public function testIsOpen($import_array, $date_time, $open)
	{
		$this->operating_hours->fromArray($import_array);
		$this->assertEquals($open, $this->operating_hours->isOpen($date_time));
	}

}

