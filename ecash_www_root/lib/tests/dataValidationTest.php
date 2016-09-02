<?php
require_once('../data_validation.2.php');

class Data_ValidationTest extends PHPUnit_Framework_TestCase
{
	private $validator;
	
	protected function setUp()
	{
		$this->validator = new Data_Validation(0, 0, 0, 0, 0);
	}
	
	public function dateProvider()
	{
		return array(
			array('03-31-2011'),
			array('03/31/2011'),
			array('2011-03-31')
		);
	}
	
	/**
	 * Tests we normalize dates into YYYY-MM-DD format.
	 * @dataProvider dateProvider
	 */
	public function testNormalizeDataDate($date)
	{
		$this->assertEquals('2011-03-31', $this->validator->Normalize_Engine($date, array('type' => 'date')));
	}
	
	/**
	 * Tests that invalid dates are returned as is.
	 */
	public function testNormalizeDataNonDate()
	{
		$this->assertEquals('foobar', $this->validator->Normalize_Engine('foobar', array('type' => 'date')));
	}
}