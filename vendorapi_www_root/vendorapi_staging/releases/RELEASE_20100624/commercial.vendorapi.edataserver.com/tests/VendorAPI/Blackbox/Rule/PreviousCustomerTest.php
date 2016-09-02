<?php

/**
 * Tests the previous customer rule
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_PreviousCustomerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var VendorAPI_Blackbox_Rule_PreviousCustomer
	 */
	protected $rule;

	/**
	 * @var VendorAPI_Blackbox_EventLog
	 */
	protected $event_log;

	/**
	 * @var ECash_CustomerHistory
	 */
	protected $customer_history;

	/**
	 * @var VendorAPI_Blackbox_Generic_Decision
	 */
	protected $decider_result;

	/**
	 * @var VendorAPI_Blackbox_ICustomerHistoryDecider
	 */
	protected $decider;

	/**
	 * @var VendorAPI_PreviousCustomer_HistoryLoader
	 */
	protected $loader;

	/**
	 * Test Fixture
	 * @return NULL
	 */
	public function setUp()
	{
		$this->event_log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);
		$this->customer_history = $this->getMock('ECash_CustomerHistory');
		$this->decider = $this->getMock('VendorAPI_Blackbox_ICustomerHistoryDecider');
		$this->decider_result = $this->getMock('VendorAPI_Blackbox_Generic_Decision', array(), array(), '', FALSE);
		$this->loader = $this->getMock('VendorAPI_PreviousCustomer_HistoryLoader', array(), array(), '', FALSE);
		$this->rule = $this->getRule();
	}

	/**
	 * Sets what the expected decision from the decider is.
	 * @param ECash_CustomerHistory $history
	 * @param bool $decision
	 */
	protected function setUpDecisionExpectation(ECash_CustomerHistory $history, $decision)
	{
		$this->decider->expects($this->once())
			->method('getDecision')
			->with($history)
			->will($this->returnValue($this->decider_result));

		$this->decider_result->expects($this->atLeastOnce())
			->method('isValid')
			->will($this->returnValue($decision));
	}

	/**
	 * Creates an instance of the rule.
	 * @param string $company
	 * @param bool $is_react
	 * @param bool $is_enterprise
	 * @return VendorAPI_Blackbox_Rule_PreviousCustomer
	 */
	protected function getRule($company = 'cmpny', $is_react = FALSE, $is_enterprise = FALSE)
	{
		return new VendorAPI_Blackbox_Rule_PreviousCustomer($this->event_log, $this->customer_history, $this->decider, $this->loader, $company, $is_react, $is_enterprise);
	}

	/**
	 * Make sure customer history is passed to the decider.
	 * @return NULL
	 */
	public function testCustomerHistoryIsPassedToDecider()
	{
		$this->setUpDecisionExpectation($this->customer_history, TRUE);
		$this->rule->isValid($this->getMock('VendorAPI_Blackbox_Data'), $this->getMock('Blackbox_IStateData'));
	}

	/**
	 * Make sure that if the decider returns true that the rule returns true.
	 * @return NULL
	 */
	public function testResultFromDecidersValidityIsCheckedAndPassesThroughTrue()
	{
		$this->setUpDecisionExpectation($this->customer_history, TRUE);
		$this->assertTrue($this->rule->isValid($this->getMock('VendorAPI_Blackbox_Data'), $this->getMock('Blackbox_IStateData')));
	}

	/**
	 * Make sure that if the decider returns false that the rule returns false.
	 * @return NULL
	 */
	public function testResultFromDecidersValidityIsCheckedAndPassesThroughFalse()
	{
		$this->setUpDecisionExpectation($this->customer_history, FALSE);
		$this->assertFalse($this->rule->isValid($this->getMock('VendorAPI_Blackbox_Data'), $this->getMock('Blackbox_IStateData')));
	}

	/**
	 * Test that failures are properly written to the event log.
	 * @return NULL
	 */
	public function testEventLogWrittenProperlyWhenFailed()
	{
		$this->setUpDecisionExpectation($this->customer_history, FALSE);

		$this->event_log->expects($this->once())->method('logEvent')->with('PREVIOUS_CUSTOMER', 'FAIL', VendorAPI_Blackbox_EventLog::FAIL);

		$this->rule->isValid($this->getMock('VendorAPI_Blackbox_Data'), $this->getMock('Blackbox_IStateData'));
	}

	/**
	 * Test that successes are properly written to the event log.
	 * @return NULL
	 */
	public function testEventLogWrittenProperlyWhenSucceeded()
	{
		$this->setUpDecisionExpectation($this->customer_history, TRUE);

		$this->decider_result->expects($this->any())->method('getDecision')->will($this->returnValue(VendorAPI_Blackbox_EventLog::PASS));

		$this->event_log->expects($this->once())->method('logEvent')->with('PREVIOUS_CUSTOMER', VendorAPI_Blackbox_EventLog::PASS, VendorAPI_Blackbox_EventLog::DEBUG);

		$this->rule->isValid($this->getMock('VendorAPI_Blackbox_Data'), $this->getMock('Blackbox_IStateData'));
	}

	/**
	 * Test that the loader is called to load the customer history object.
	 * @return NULL
	 */
	public function testLoaderCalled()
	{
		$this->setUpDecisionExpectation($this->customer_history, TRUE);

		$data = new VendorAPI_Blackbox_Data();
		$data->bank_aba = 123456789;

		$this->loader->expects($this->once())
			->method('loadHistoryObject')
			->with($this->customer_history, $data->toArray());

		$this->rule->isValid($data, $this->getMock('Blackbox_IStateData'));
	}

	/**
	 * Test that the customer history is limited to a single company when the application is a react.
	 * @return NULL
	 */
	public function testCustomerHistoryReducedOnReact()
	{
		$company_history = $this->getMock('ECash_CustomerHistory');

		$this->setUpDecisionExpectation($company_history, TRUE);

		$this->customer_history->expects($this->any())
			->method('getCompanyHistory')
			->with('cmpny')
			->will($this->returnValue($company_history));

		$rule = $this->getRule('cmpny', TRUE);
		$rule->isValid($this->getMock('VendorAPI_Blackbox_Data'), $this->getMock('Blackbox_IStateData'));
	}

	/**
	 * Test that customer history is NOT reduced if the loan is a non react from an enterprise.
	 * @return NULL
	 */
	public function testCustomerHistoryNotReducedOnEnterpriseNonReact()
	{
		$company_history = $this->getMock('ECash_CustomerHistory');

		$this->setUpDecisionExpectation($this->customer_history, TRUE);

		$this->customer_history->expects($this->any())
			->method('getIsReact')
			->with('cmpny')
			->will($this->returnValue(FALSE));

		$rule = $this->getRule('cmpny', FALSE, TRUE);
		$rule->isValid($this->getMock('VendorAPI_Blackbox_Data'), $this->getMock('Blackbox_IStateData'));
	}

	/**
	 * Test that customer history is reduced on enterprise reacts.
	 * @return NULL
	 */
	public function testCustomerHistoryReducedOnEnterpriseReact()
	{
		$company_history = $this->getMock('ECash_CustomerHistory');

		$this->setUpDecisionExpectation($company_history, TRUE);

		$this->customer_history->expects($this->any())
			->method('getCompanyHistory')
			->with('cmpny')
			->will($this->returnValue($company_history));

		$this->customer_history->expects($this->any())
			->method('getIsReact')
			->with('cmpny')
			->will($this->returnValue(TRUE));

		$rule = $this->getRule('cmpny', FALSE, TRUE);
		$rule->isValid($this->getMock('VendorAPI_Blackbox_Data'), $this->getMock('Blackbox_IStateData'));
	}
}

?>
