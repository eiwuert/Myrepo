<?php

// Qualify.php doesn't get included until after setUp, and LAC needs to
// exist before we mock it (or PHPUnit will declare a dummy class)
require_once 'bootstrap.php';
require_once LIB_DIR.'/status_utility.class.php';
require_once ECASH_COMMON_DIR.'/ecash_api/loan_amount_calculator.class.php';

class ECash_VendorAPI_LoanAmountCalculatorTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var LoanAmountCalculator
	 */
	protected $_lac;

	/**
	 * @var ECash_VendorAPI_LoanAmountCalculator
	 */
	protected $_calculator;

	/**
	 * @var array
	 */
	protected $_rules;

	protected $_data_new;
	protected $_data_react;
	protected $_expected_new;
	protected $_expected_react;

	public function setUp()
	{
		$this->_lac = $this->getMock(
			'LoanAmountCalculator',
			array(
				'calculateMaxLoanAmount',
				'calculateLoanAmountsArray',
			),
			array(),
			'',
			FALSE
		);

		$this->_rules = array(
			'new_loan_amount' => array(1500=>150, 2500=>200, 3000=>300),
			'max_react_loan_amount' => array(500),
			'react_amount_increase' => 50,
			'minimum_loan_amount' => array(
				'min_react' => 100,
				'min_non_react' => 100,
			),
		);

		// fixture for new loan tests
		$this->_data_new = array(
			'income_monthly' => 1000,
			'num_paid_applications' => 0,
			'is_react' => FALSE,
			'application_list' => array(),
			'income_frequency' => 'weekly',
			'idv_increase_eligible' => FALSE,
		);

		// fixture for react tests
		$this->_data_react = array(
			'income_monthly' => 1000,
			'num_paid_applications' => 2,
			'is_react' => TRUE,
			'application_list' => array(),
			'income_frequency' => 'weekly',
			'idv_increase_eligible' => FALSE,
			'react_application_id' => 1,
		);

		// expectation for new loan tests
		$this->_expected_new = (object)array(
			'business_rules' => $this->_rules,
			'loan_type_name' => 'test',
			'income_monthly' => 1000,
			'is_react' => 'no',
			'num_paid_applications' => 0,
			'react_app_id' => '',
			'application_list' => array(),
			'payperiod' => 'weekly',
			'idv_increase_eligible' => FALSE,
		);

		// expectation for react tests
		$this->_expected_react = (object)array(
			'business_rules' => $this->_rules,
			'loan_type_name' => 'test',
			'income_monthly' => 1000,
			'is_react' => 'yes',
			'num_paid_applications' => 2,
			'react_app_id' => 1,
			'application_list' => array(),
			'payperiod' => 'weekly',
			'idv_increase_eligible' => FALSE,
		);

		$this->_calculator = new ECash_VendorAPI_LoanAmountCalculator($this->_lac, $this->_rules, 'test');
	}

	public function testNewApplicationDataPassedToLAC()
	{
		$data = $this->_data_new;

		$this->_lac->expects($this->any())
			->method('calculateMaxLoanAmount')
			->with($this->equalTo($this->_expected_new));

		$this->_calculator->getMaximumAmount($data);
	}

	public function testReactApplicationDataPassedToLAC()
	{
		$data = $this->_data_react;

		$this->_lac->expects($this->any())
			->method('calculateMaxLoanAmount')
			->with($this->equalTo($this->_expected_react));

		$this->_calculator->getMaximumAmount($data);
	}

	public function testGetAmountIncrementPassesNewData()
	{
		$data = $this->_data_new;

		$this->_lac->expects($this->any())
			->method('calculateLoanAmountsArray')
			->with($this->equalTo($this->_expected_new));

		$this->_calculator->getAmountIncrements($data);
	}

	public static function falseIsReactProvider()
	{
		return array(
			array('no', 'yes'),
			array(0, 'no'),
			array(TRUE, 'yes'),
			array(NULL, 'no'), // unset
			array('YES', 'yes'),
			array('yes', 'yes'),
		);
	}

	/**
	 * Tests that the various values in falseIsReactProvider
	 * are all properly converted to 'no'.
	 *
	 * @dataProvider falseIsReactProvider
	 * @param mixed $is_react
	 */
	public function testIsReactConversionInCalculateMaxAmount($actual, $expected)
	{
		$data = $this->_data_new;

		// now, mutate the is_react value...
		if ($actual !== NULL) $data['is_react'] = $actual;
		else unset($data['is_react']);

		// set the expectation
		$expected_data = $this->_expected_new;
		$expected_data->is_react = $expected;

		$this->_lac->expects($this->any())
			->method('calculateMaxLoanAmount')
			->with($this->equalTo($expected_data));

		$this->_calculator->getMaximumAmount($data);
	}

	/**
	 * Tests that the various values in falseIsReactProvider
	 * are all properly converted to 'no'.
	 *
	 * @dataProvider falseIsReactProvider
	 * @param mixed $is_react
	 */
	public function testIsReactConversionInCalculateAmountIncrements($actual, $expected)
	{
		$data = $this->_data_new;

		// now, mutate the is_react value...
		if ($actual !== NULL) $data['is_react'] = $actual;
		else unset($data['is_react']);

		// set the expectation
		$expected_data = $this->_expected_new;
		$expected_data->is_react = $expected;

		$this->_lac->expects($this->any())
			->method('calculateLoanAmountsArray')
			->with($this->equalTo($expected_data));

		$this->_calculator->getAmountIncrements($data);
	}
}

?>