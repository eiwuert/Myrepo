<?php

class VendorAPI_Blackbox_Rule_PricedDataXTest extends PHPUnit_Framework_TestCase
{
	protected $_response;
	protected $_result;
	/**
	 * @var PHPUnit_Framework_MockObject_Mock
	 */
	protected $_call;
	protected $_rule;
	protected $_data;
	protected $_state;

	public function setUp()
	{
		$this->_response = $this->getMock('TSS_DataX_IPricedResponse', array('getLeadCost', 'hasError', 'getTrackHash', 'isSoftFailure'));
		$this->_result = $this->getMock('TSS_DataX_Result', array('isValid', 'getResponse'), array(), '', FALSE);
		$this->_result->expects($this->any())
			->method('getResponse')
			->will($this->returnValue($this->_response));

		$this->_call = $this->getMock('TSS_DataX_Call', array('execute'), array(), '', FALSE);
		$this->_call->expects($this->any())
			->method('execute')
			->will($this->returnValue($this->_result));

		$event_log = $this->getMock('VendorAPI_Blackbox_EventLog', array('logEvent'), array(), '', FALSE);

		$this->_data = new VendorAPI_Blackbox_Data();
		$this->_state = new VendorAPI_Blackbox_StateData();

		$this->_rule = new VendorAPI_Blackbox_Rule_PricedDataX(
			$event_log,
			$this->_call,
			100,
			FALSE
		);
	}

	public function tearDown()
	{
		$this->_response = null;
		$this->_result = null;
		$this->_call = null;
		$this->_rule = null;
		$this->_state = null;
		$this->_data = null;
	}

	public function testPassesPricePointToDataX()
	{
		$this->_response->expects($this->any())
			->method('hasError')
			->will($this->returnValue(FALSE));
		$this->_result->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));

		// ensure we get the lead_cost key
		$this->_call->expects($this->atLeastOnce())
			->method('execute')
			->with($this->arrayHasKey('lead_cost'));

		$this->_rule->isValid(
			$this->_data,
			$this->_state
		);
	}

	public function testStoresLeadCostInStateDataOnValidResponse()
	{
		$this->_response->expects($this->any())
			->method('hasError')
			->will($this->returnValue(FALSE));
		$this->_result->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));

		$this->_response->expects($this->any())
			->method('getLeadCost')
			->will($this->returnValue(50));

		$this->_rule->isValid(
			$this->_data,
			$this->_state
		);

		$this->assertEquals(50, $this->_state->lead_cost);
	}

	public function testStoresLeadCostInStateDataOnInvalidResponse()
	{
		$this->_response->expects($this->any())
			->method('hasError')
			->will($this->returnValue(FALSE));
		$this->_result->expects($this->any())
			->method('isValid')
			->will($this->returnValue(FALSE));

		$this->_response->expects($this->any())
			->method('getLeadCost')
			->will($this->returnValue(50));

		$this->_rule->isValid(
			$this->_data,
			$this->_state
		);

		$this->assertEquals(50, $this->_state->lead_cost);
	}

	public function testDoesNotRunWhenPricePointIsGreaterThanLeadCost()
	{
		$this->_response->expects($this->any())
			->method('hasError')
			->will($this->returnValue(FALSE));

		$this->_result->expects($this->never())
			->method('isValid')
			->will($this->returnValue(TRUE));

		// price point is 100; lead cost MUST be lower
		$this->_state->lead_cost = 20;

		$this->_rule->isValid(
			$this->_data,
			$this->_state
		);
	}

	public function testCachesDecisionOnSuccess()
	{
		$this->_response->expects($this->any())
			->method('hasError')
			->will($this->returnValue(FALSE));
		$this->_result->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));

		$this->_rule->isValid(
			$this->_data,
			$this->_state
		);

		$this->assertTrue($this->_state->datax_decision);
	}

	public function testCachesDecisionOnHardFailure()
	{
		$this->_response->expects($this->any())
			->method('hasError')
			->will($this->returnValue(FALSE));
		$this->_result->expects($this->any())
			->method('isValid')
			->will($this->returnValue(FALSE));
		$this->_response->expects($this->any())
			->method('isSoftFailure')
			->will($this->returnValue(FALSE));

		$this->_rule->isValid(
			$this->_data,
			$this->_state
		);

		$this->assertFalse($this->_state->datax_decision);
	}

	public function testClearsCachedDecisionOnSoftFailure()
	{
		$this->_response->expects($this->any())
			->method('hasError')
			->will($this->returnValue(FALSE));
		$this->_result->expects($this->any())
			->method('isValid')
			->will($this->returnValue(FALSE));
		$this->_response->expects($this->any())
			->method('isSoftFailure')
			->will($this->returnValue(TRUE));

		$this->_rule->isValid(
			$this->_data,
			$this->_state
		);

		$this->assertNull($this->_state->datax_decision);
	}
}

?>