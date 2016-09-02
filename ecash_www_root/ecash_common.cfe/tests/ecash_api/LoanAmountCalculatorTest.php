<?php

require_once 'config.php';

class LoanAmountCalculatorTest extends PHPUnit_Framework_TestCase
{
	public static function maxAmountProvider()
	{
		return array(
			// amount, is_react, paid_loans, expected_amount
			array(50, FALSE, 0, 300),
			array(100, FALSE, 0, 350), // values in amount array are >=
			array(150, TRUE, 1, 400), // amount should go up by react increase
			array(300, TRUE, 4, 500), // limited to max react amount
			array(800, FALSE, 0, 400), // limited to max amount
		);
	}

	public static function amountsProvider()
	{
		return array(
			// amount, is_react, paid_loans, expected_amounts
			array(50, FALSE, 0, array(300, 250, 200)),
			array(150, TRUE, 1, array(250, 300, 350, 400)),
		);
	}

	public static function exceptionProvider()
	{
		return array(
			array(NULL),
			array('test'),
			array(1),
			array(array()),
			array(new stdClass()),
			array((object)array('business_rules' => array())),
			array((object)array('business_rules' => array(), 'income_monthly' => 100)),
			//array((object)array('business_rules' => array(), 'income_monthly' => 100, 'is_react' => 'no')),
		);
	}

	public static function companyTypeProvider()
	{
		return array(
			array('generic', 'LoanAmountCalculator'),
		);
	}

	private $_calc;
	private $_rules;

	public function setUp()
	{
		$this->requireFile(ECASH_COMMON_DIR.'ecash_api/loan_amount_calculator.class.php');

		$this->_calc = new LoanAmountCalculator(NULL);
		$this->_calc = $this->getMock(
			'LoanAmountCalculator',
			array('countNumberPaidApplications'),
			array(NULL)
		);

		$this->_rules = array(
			'new_loan_amount' => array(
				100 => 300,
				200 => 350,
				300 => 400,
			),
			'react_amount_increase' => 50,
			'max_react_loan_amount' => array(500),
			'minimum_loan_amount' => array(
				'min_react' => 250,
				'min_non_react' => 200,
			),
		);
	}

	/**
	 * @dataProvider maxAmountProvider
	 */
	public function testMaxLoanAmount($income, $is_react, $paid_loans, $expected_amount)
	{
		$data = new stdClass();
		$data->business_rules = $this->_rules;
		$data->income_monthly = $income;
		$data->is_react = ($is_react ? 'yes' : 'no');

		$this->_calc->expects($this->any())
			->method('countNumberPaidApplications')
			->will($this->returnValue($paid_loans));

		$actual_amount = $this->_calc->calculateMaxLoanAmount($data);
		$this->assertEquals($expected_amount, $actual_amount);
	}

	/**
	 * @dataProvider amountsProvider
	 */
	public function testLoanAmounts($income, $is_react, $paid_loans, array $expected_amounts)
	{
		$data = new stdClass();
		$data->business_rules = $this->_rules;
		$data->income_monthly = $income;
		$data->is_react = ($is_react ? 'yes' : 'no');

		$this->_calc->expects($this->any())
			->method('countNumberPaidApplications')
			->will($this->returnValue($paid_loans));

		$actual_amounts = $this->_calc->calculateLoanAmountsArray($data);
		$this->assertEquals($expected_amounts, $actual_amounts);
	}

	/**
	 * @dataProvider exceptionProvider
	 */
	public function testCalculateMaxLoanAmountThrowsExceptions($data)
	{
		$this->setExpectedException('Exception');
		$this->_calc->calculateMaxLoanAmount($data);
	}

	/**
	 * @dataProvider exceptionProvider
	 */
	public function testCalculateLoanAmountsThrowsExceptions($data)
	{
		$this->setExpectedException('Exception');
		$this->_calc->calculateLoanAmountsArray($data);
	}

	/**
	 * @param string $company
	 * @param string $expected_type
	 * @dataProvider companyTypeProvider
	 */
	public function testGetInstanceReturnsProperType($company, $expected_type)
	{
		$db = new DB_Database_1('mysql:blah');

		$actual = LoanAmountCalculator::Get_Instance($db, $company);
		$this->assertType($expected_type, $actual);
	}

	protected function requireFile($filename)
	{
		if (!include_once($filename))
		{
			$this->markTestIncomplete('Could not include required file');
		}
	}
}

?>
