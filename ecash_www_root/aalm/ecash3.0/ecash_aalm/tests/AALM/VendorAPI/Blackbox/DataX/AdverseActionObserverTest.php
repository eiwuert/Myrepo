<?php

class AALM_VendorAPI_Blackbox_DataX_AdverseActionObserverTest extends PHPUnit_Framework_TestCase
{
	protected $_client;
	protected $_rule;
	protected $_data;

	/**
	 * @var AALM_VendorAPI_Blackbox_DataX_AdverseActionObserver
	 */
	protected $_observer;

	public function setUp()
	{
		$this->_client = $this->getMock('VendorAPI_StatProClient', array('hitStat'), array(), '', FALSE);
		$this->_rule = $this->getMock('VendorAPI_Blackbox_Rule_DataX', array(), array(), '', FALSE);
		$this->_observer = new AALM_VendorAPI_Blackbox_DataX_AdverseActionObserver('generic', $this->_client);
		$this->_data = new VendorAPI_Blackbox_Data();
	}

	public function tearDown()
	{
		$this->_client = NULL;
		$this->_observer = NULL;
		$this->_rule = NULL;
	}

	public function testIDVFailureHitsGenericStat()
	{
		$result = $this->getResult(FALSE, array('IDV' => 'D1'), 'IDV', 'N');

		$this->_client->expects($this->once())
			->method('hitStat')
			->with('aa_denial_datax_entgen');

		$this->_observer->onCall(
			$this->_rule,
			$result,
			NULL, $this->_data
		);
	}

	public function testCRAFailureHitsCRAStat()
	{
		$result = $this->getResult(FALSE, array('CRA' => 'D1'), 'CRA', 'N');

		$this->_client->expects($this->once())
			->method('hitStat')
			->with('aa_aalm_cra_denial');

		$this->_observer->onCall(
			$this->_rule,
			$result,
			NULL, $this->_data
		);
	}

	public function testTLTFailureHitsTeletrackStat()
	{
		$result = $this->getResult(FALSE, array('TLT' => 'D1'), 'TLT', 'N');

		$this->_client->expects($this->once())
			->method('hitStat')
			->with('aa_aalm_teletrack_denial');

		$this->_observer->onCall(
			$this->_rule,
			$result,
			NULL, $this->_data
		);
	}

	public function testSuccessAddsNoAction()
	{
		$result = $this->getResult(TRUE, array(), '', NULL);

		$this->_client->expects($this->never())
			->method('hitStat');

		$this->_observer->onCall(
			$this->_rule,
			$result,
			NULL, $this->_data
		);
	}

	protected function getResult($valid, array $buckets, $segment, $decision)
	{
		$response = $this->getMock(
			'AALM_DataX_Responses_Perf',
			array('isValid', 'getDecisionBuckets', 'getBucketDecision', 'getSegmentDecision')
		);

		$response->expects($this->any())
			->method('isValid')
			->will($this->returnValue($valid));
		$response->expects($this->any())
			->method('getDecisionBuckets')
			->will($this->returnValue($buckets));

		if ($segment && $decision)
		{
			$response->expects($this->any())
				->method('getSegmentDecision')
				->with($segment)
				->will($this->returnValue($decision));
		}

		return new TSS_DataX_Result('', 0, '', '', $response);
	}
}

?>
