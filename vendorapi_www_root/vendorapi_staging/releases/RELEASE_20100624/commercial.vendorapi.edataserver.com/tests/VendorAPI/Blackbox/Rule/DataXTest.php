<?php

class VendorAPI_Blackbox_Rule_DataXTest extends PHPUnit_Framework_TestCase
{
	protected $_state;
	protected $_data;
	protected $_result;
	protected $_response;
	protected $_call;
	protected $_history;
	protected $_log;
	protected $_rule;

	protected function setUp()
	{
		$this->_history = $this->getMock('VendorAPI_Blackbox_DataX_CallHistory', array('addResult', 'getResult'), array(), '', FALSE);

		$this->_state = new VendorAPI_Blackbox_StateData();
		$this->_state->datax_call_history = $this->_history;

		$this->_data = new VendorAPI_Blackbox_Data();
		$this->_response = $this->getMock('TSS_DataX_IPerformanceResponse', array('hasError', 'getDecision', 'getScore','getDecisionBuckets','isValid','parseXML', 'isIDVFailure', 'getErrorMsg','getErrorCode','getTrackHash'), array(), '', FALSE);//, array('isValid', 'getTrackHash', 'isIDVFailure'));

		$this->_result = new TSS_DataX_Result('test', 0, '', '', $this->_response);

		$this->_call = $this->getMock('TSS_DataX_Call', array('execute', 'getCallType'), array(), '', FALSE);
		$this->_call->expects($this->any())
			->method('execute')
			->will($this->returnValue($this->_result));
		$this->_call->Expects($this->any())
			->method('getCallType')
			->will($this->returnValue('test'));

		$this->_log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);

		$this->_rule = new VendorAPI_Blackbox_Rule_DataX($this->_log, $this->_call);
	}

	protected function tearDown()
	{
		$this->_response = NULL;
		$this->_call = NULL;
		$this->_history = NULL;
		$this->_rule = NULL;
		$this->_log = NULL;
	}

	public function testRecordsFailuresInHistory()
	{
		$this->_response->expects($this->any())
			->method('isValid')
			->will($this->returnValue(FALSE));

		$this->_history->expects($this->once())
			->method('addResult')
			->with($this->_result);

		$this->_rule->isValid($this->_data, $this->_state);
	}

	public function testRecordsPassesInHistory()
	{
		$this->_response->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));

		$this->_history->expects($this->once())
			->method('addResult')
			->with($this->_result);

		$this->_rule->isValid($this->_data, $this->_state);
	}

	/*public function testReusesExistingResultFromHistory()
	{
		$hist = $this->getMock('TSS_DataX_IResponse');
		$hist->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		$this->_history->expects($this->once())
			->method('getResult')
			->will($this->returnValue($hist));

		// this should never actually get called, but
		// it'll make our assertion failed if it does
		$this->_response->expects($this->any())
			->method('isValid')
			->will($this->returnValue(FALSE));

		$valid = $this->_rule->isValid($this->_data, $this->_state);
		$this->assertTrue($valid);
	}*/

	public function testUsesExistingTrackHashFromStateData()
	{
		$this->_state->datax_track_hash = 'aaaaaaaaaa';

		$this->_call->expects($this->any())
			->method('execute')
			->with($this->contains('aaaaaaaaaa'));

		$this->_rule->isValid($this->_data, $this->_state);
	}

	public function testSavesTrackHashInStateData()
	{
		$this->_response->expects($this->any())
			->method('isValid')
			->will($this->returnValue(FALSE));

		$this->_response->expects($this->any())
			->method('getTrackHash')
			->will($this->returnValue('aaaaaaaaaa'));

		$this->_rule->isValid($this->_data, $this->_state);
		$this->assertEquals('aaaaaaaaaa', $this->_state->datax_track_hash);
	}

	public function testStripsHTTPFromURL()
	{
		// I'd rather not use contains, but can't come up with a better way yet...
		$this->_call->expects($this->atLeastOnce())
			->method('execute')
			->with($this->contains('www.test.com'));

		$this->_data->client_url_root = 'http://www.test.com';
		$this->_rule->isValid($this->_data, $this->_state);
	}

	public function testStripsHTTPSFromURL()
	{
		// I'd rather not use contains, but can't come up with a better way yet...
		$this->_call->expects($this->atLeastOnce())
			->method('execute')
			->with($this->contains('www.test.com'));

		$this->_data->client_url_root = 'https://www.test.com';
		$this->_rule->isValid($this->_data, $this->_state);
	}

	public function testIDVFailureThrowsReworkException()
	{
		$this->_response->expects($this->any())
			->method('isValid')
			->will($this->returnValue(FALSE));

		$this->_response->expects($this->any())
			->method('isIDVFailure')
			->will($this->returnValue(TRUE));

		$this->setExpectedException('VendorAPI_Blackbox_DataX_ReworkException');

		$log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);
		$rule = new VendorAPI_Blackbox_Rule_DataX($log, $this->_call, TRUE);
		$rule->isValid($this->_data, $this->_state);
	}

	public function testFailsOnProviderException()
	{
		$this->_call->expects($this->any())
			->method('execute')
			->will($this->throwException(new TSS_DataX_ProviderException('', 0, $this->_response)));

		$valid = $this->_rule->isValid($this->_data, $this->_state);
		$this->assertFalse($valid);
	}

	public function testPassesOnTransportException()
	{
		$this->_call->expects($this->any())
			->method('execute')
			->will($this->throwException(new TSS_DataX_TransportException('', 0)));

		$valid = $this->_rule->isValid($this->_data, $this->_state);
		$this->assertTrue($valid);
	}

	public function testLoanAmountDecision()
	{
		$this->_response = $this->getMock('TSS_DataX_ILoanAmountResponse', array('hasError', 'getDecision', 'getScore','getDecisionBuckets','isValid','parseXML', 'isIDVFailure', 'getErrorMsg','getErrorCode','getTrackHash', 'getLoanAmountDecision'), array(), '', FALSE);//, array('isValid', 'getTrackHash', 'isIDVFailure')););
		$this->_response->expects($this->once())
			->method('getLoanAmountDecision')
			->will($this->returnValue(TRUE));

		$this->_result = new TSS_DataX_Result('test', 0, '', '', $this->_response);

		$this->_call = $this->getMock('TSS_DataX_Call', array('execute', 'getCallType'), array(), '', FALSE);
		$this->_call->expects($this->any())
			->method('execute')
			->will($this->returnValue($this->_result));
		$this->_call->Expects($this->any())
			->method('getCallType')
			->will($this->returnValue('test'));

		$log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);

		$this->_rule = new VendorAPI_Blackbox_Rule_DataX($log, $this->_call);

		$this->_rule->isValid($this->_data, $this->_state);
		$this->assertTrue($this->_state->loan_amount_decision);
	}

	public function testCachesDecisionOnSuccess()
	{
		$this->_response->expects($this->any())
			->method('hasError')
			->will($this->returnValue(FALSE));
		$this->_response->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));

		$this->_rule->isValid(
			$this->_data,
			$this->_state
		);

		$this->assertTrue($this->_state->datax_decision);
	}

	public function testCachesDecisionOnFailure()
	{
		$this->_response->expects($this->any())
			->method('hasError')
			->will($this->returnValue(FALSE));
		$this->_response->expects($this->any())
			->method('isValid')
			->will($this->returnValue(FALSE));

		$this->_rule->isValid(
			$this->_data,
			$this->_state
		);

		$this->assertFalse($this->_state->datax_decision);
	}

	public function testDoesNotCacheDecisionOnRework()
	{
		$this->_response->expects($this->any())
			->method('hasError')
			->will($this->returnValue(FALSE));
		$this->_response->expects($this->any())
			->method('isValid')
			->will($this->returnValue(FALSE));
		$this->_response->expects($this->any())
			->method('isIDVFailure')
			->will($this->returnValue(TRUE));

		// ignore rework exception without using setExceptedException,
		// which actually sets an expectation/assertion
		try
		{
			$rule = new VendorAPI_Blackbox_Rule_DataX($this->_log, $this->_call, TRUE);
			$rule->isValid(
				$this->_data,
				$this->_state
			);
		}
		catch (Exception $e)
		{
		}

		$this->assertNull($this->_state->datax_decision);
	}

	public function testCachedFailureIsReusedWithoutCallingDataX()
	{
		$this->_state->datax_decision = FALSE;

		$this->_call->expects($this->never())
			->method('execute');

		$this->_rule->isValid(
			$this->_data,
			$this->_state
		);
	}

	public function testCachedSuccessIsReusedWithoutCallingDataX()
	{
		$this->_state->datax_decision = TRUE;

		$this->_call->expects($this->never())
			->method('execute');

		$this->_rule->isValid(
			$this->_data,
			$this->_state
		);
	}
}
