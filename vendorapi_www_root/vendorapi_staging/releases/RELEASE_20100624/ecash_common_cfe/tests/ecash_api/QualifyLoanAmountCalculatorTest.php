<?php

require_once 'config.php';

/**
 * Based on LoanAmountCalculatorTest
 *
 */
class QualifyLoanAmountCalculatorTest extends PHPUnit_Framework_TestCase
{
	public static function newLoanAmountProvider()
	{
		return array(
			// amount, is_react, paid_loans, expected_amount
			array(50, FALSE, 300),
			array(100, FALSE, 350), // values in amount array are >=
			//array(150, TRUE, 1, 400), // amount should go up by react increase
			//array(300, TRUE, 4, 500), // limited to max react amount
			array(800, FALSE, 400), // limited to max amount
			array(50, TRUE, 400),
		);
	}

	public static function newLoanAmountsProvider()
	{
		return array(
			// amount, is_react, paid_loans, expected_amounts
			array(50, FALSE, array(300, 250, 200)),
			//array(150, TRUE, 1, array(250, 300, 350, 400)),
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
			array((object)array('business_rules' => array(), 'income_monthly' => 100, 'is_react' => 'yes')),
		);
	}

	private $_calc;
	private $_rules;

	public function setUp()
	{
		$this->requireFile(ECASH_COMMON_DIR.'ecash_api/qualify.2.ecash.php');
		$this->requireFile(ECASH_COMMON_DIR.'ecash_api/loan_amount_calculator.class.php');
		$this->requireFile(ECASH_COMMON_DIR.'ecash_api/qualify_loan_amount_calculator.class.php');

		$this->_calc = $this->getMock(
			'QualifyLoanAmountCalculator',
			array('countNumberPaidApplications'),
			array(NULL)
		);

		$this->_rules = array(
			'new_loan_amount' => array(
				100 => 300,
				200 => 350,
				300 => 400,
			),
			'datax_amount_increase' => 100,
			'react_amount_increase' => 50,
			'max_react_loan_amount' => array(500),
			'minimum_loan_amount' => array(
				'min_react' => 250,
				'min_non_react' => 200,
			),
		);
	}

	/**
	 * @dataProvider newLoanAmountProvider
	 */
	public function testMaxLoanAmount($income, $eligible, $expected_amount)
	{
		$data = new stdClass();
		$data->business_rules = $this->_rules;
		$data->income_monthly = $income;
		$data->is_react = 'no';
		$data->react_app_id = 0;
		$data->idv_increase_eligible = $eligible;
		$data->payperiod = '';

		$actual_amount = $this->_calc->calculateMaxLoanAmount($data);
		$this->assertEquals($expected_amount, $actual_amount);
	}

	/**
	 * @dataProvider newLoanAmountsProvider
	 */
	public function testLoanAmounts($income, $eligible, array $expected_amounts)
	{
		$data = new stdClass();
		$data->business_rules = $this->_rules;
		$data->income_monthly = $income;
		$data->is_react = 'no';
		$data->react_app_id = 0;
		$data->idv_increase_eligible = $eligible;
		$data->payperiod = '';

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

	protected function requireFile($filename)
	{
		if (!require_once($filename))
		{
			$this->markTestIncomplete('Could not include required file');
		}
	}
}

?>
