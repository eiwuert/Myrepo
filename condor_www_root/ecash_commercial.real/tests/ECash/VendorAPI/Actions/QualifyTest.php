<?php

// Qualify.php doesn't get included until after setUp, and LAC needs to
// exist before we mock it (or PHPUnit will declare a dummy class)
require_once ECASH_COMMON_DIR.'/ecash_api/loan_amount_calculator.class.php';

/**
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class ECash_VendorAPI_Actions_QualifyTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var ECash_CFE_AsynchEngine
	 */
	protected $_engine;

	/**
	 * @var ECash_VendorAPI_Driver
	 */
	protected $_driver;

	/**
	 * @var ECash_VendorAPI_Actions_Qualify
	 */
	protected $_action;

	/**
	 * @var VendorAPI_ILoanAmountCalculator
	 */
	protected $_calculator;

	/**
	 * Qualify call parameters
	 * @var array
	 */
	protected $_data;

	public function setUp()
	{
		$this->_data = array(
			'income_monthly' => 1000,
			'num_paid_applications' => 0,
			'is_react' => FALSE,
			'application_list' => array(),
			'income_frequency' => 'weekly',
		);

		$this->_engine = $this->getMock(
			'ECash_CFE_AsynchEngine',
			array('beginExecution'),
			array(),
			'',
			FALSE
		);

		$this->_calculator = $this->getMock(
			'LoanAmountCalculator',
			array('calculateMaxLoanAmount'),
			array(), '', FALSE
		);

		$winner = new VendorAPI_Blackbox_Winner(
			new VendorAPI_Blackbox_Target(new VendorAPI_Blackbox_StateData()),
			new VendorAPI_Blackbox_CustomerHistory()
		);

		$this->_driver = $this->getMock(
			'ECash_VendorAPI_Driver',
			array('getCompany'),
			array(),
			'',
			FALSE
		);

		$this->_driver->expects($this->any())
			->method('getCompany')
			->will($this->returnValue('ttt'));

		$this->_action = $this->getMock(
			'ECash_VendorAPI_Actions_Qualify',
			array('getEngine', 'getBusinessRules', 'getECashCalculator', 'getBlackboxWinner'),
			array($this->_driver)
		);

		$this->_action->expects($this->any())
			->method('getEngine')
			->will($this->returnValue($this->_engine));

		$this->_action->expects($this->any())
			->method('getBusinessRules')
			->will($this->returnValue(array()));

		$this->_action->expects($this->any())
			->method('getECashCalculator')
			->will($this->returnValue($this->_calculator));

		$this->_action->expects($this->any())
			->method('getBlackboxWinner')
			->will($this->returnValue($winner));
	}

	public function testQualifiesIsFalseWhenCFEFails()
	{
		// null ruleset_id indicates failure...
		$engine_result = new ECash_CFE_AsynchResult(null);

		$this->_engine->expects($this->any())
			->method('beginExecution')
			->will($this->returnValue($engine_result));

		$response = $this->_action->execute($this->_data);
		$result = $response->getResult();

		$this->assertArrayHasKey('qualified', $result);
		$this->assertFalse($result['qualified']);
	}

	public function testQualifiesIsTrueWhenCFEPasses()
	{
		// null ruleset_id indicates failure...
		$engine_result = new ECash_CFE_AsynchResult(1, array('loan_type_id' => 1, 'name_short' => 'test'));

		$this->_engine->expects($this->any())
			->method('beginExecution')
			->will($this->returnValue($engine_result));

		$response = $this->_action->execute($this->_data);
		$result = $response->getResult();

		$this->assertArrayHasKey('qualified', $result);
		$this->assertTrue($result['qualified']);
	}
}

?>