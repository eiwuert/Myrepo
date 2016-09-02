<?php

class Date_Util1Test extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testGetAge().
	 *
	 * @return array
	 */
	public static function dataProviderGetAge()
	{
		return array(
			array(
				strtotime('2000-01-01'),
				strtotime('2008-01-01'),
				8,
			),
			
			array(
				strtotime('2000-01-02'),
				strtotime('2008-01-01'),
				7,
			),
			
			array(
				strtotime('2000-01-01'),
				strtotime('2008-01-02'),
				8,
			),
			
			array(
				strtotime('2000-01-01'),
				strtotime('2007-12-31'),
				7,
			),
			
			array(
				strtotime('2000-02-29'),
				strtotime('2008-02-28'),
				7,
			),
			
			array(
				strtotime('2000-02-29'),
				strtotime('2008-02-29'),
				8,
			),
			
			array(
				strtotime('2000-02-29'),
				strtotime('2008-03-01'),
				8,
			),
			
			array(
				strtotime('2000-02-29'),
				strtotime('2007-03-01'),
				7,
			),
			
			array(
				strtotime('2000-01-01'),
				strtotime('2008-01-01 23:59:59'),
				8,
			),
			
			array(
				strtotime('2000-01-01'),
				strtotime('2007-12-31 23:59:59'),
				7,
			),
			
			array(
				strtotime('2000-01-01 23:59:59'),
				strtotime('2008-01-01'),
				8,
			),
			
			array(
				strtotime('2000-01-01'),
				strtotime('2000-01-01'),
				0,
			),
			
			array(
				strtotime('2008-01-01'),
				strtotime('2000-01-01'),
				0,
			),
		);
	}
	
	/**
	 * Tests getAge().
	 *
	 * @dataProvider dataProviderGetAge
	 *
	 * @param int $date_of_birth
	 * @param int $compare_date
	 * @param int $expect_age
	 * @return void
	 */
	public function testGetAge($date_of_birth, $compare_date, $expect_age)
	{
		$age = Date_Util_1::getAge($date_of_birth, $compare_date);
		
		$this->assertEquals($expect_age, $age, sprintf("Getting the age of %s during the time of %s",
			date('Y-m-d H:i:s', $date_of_birth),
			date('Y-m-d H:i:s', $compare_date)
		));
	}

	public function testDateDiffWithStrings()
	{
		$diff = Date_Util_1::dateDiff('2009-10-01', '2009-10-02');
		$this->assertEquals(1, $diff);
	}

	public function testDateDiffWithTimestamps()
	{
		$diff = Date_Util_1::dateDiff(strtotime('2009-10-01'), strtotime('2009-10-02'));
		$this->assertEquals(1, $diff);
	}

	public function testDateDiffWithMixedTypes()
	{
		$diff = Date_Util_1::dateDiff(strtotime('2009-10-01'), '2009-10-02');
		$this->assertEquals(1, $diff);
	}
}

?>
