<?php

class AALM_DataX_Responses_Perf_IDVFailTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var AALM_DataX_Responses_Perf
	 */
	protected $_response;

	public function setUp()
	{
		$this->_response = new AALM_DataX_Responses_Perf();
		$this->_response->parseXML($this->getFixture('aalm_fail_idv'));
	}

	public function testIsValidIsFalse()
	{
		$this->assertFalse($this->_response->isValid());
	}

	public function testDecisionIsNo()
	{
		$this->assertEquals('N', $this->_response->getDecision());
	}

	public function testIDVBucketContainsD1()
	{
		$this->assertBucketContains('D1', 'IDV');
	}

	public function testIsIDVFailureIsTrue()
	{
		$this->assertTrue($this->_response->isIDVFailure());
	}

	public function testIDVSegmentDecisionIsNo()
	{
		$decision = $this->_response->getSegmentDecision('IDV');
		$this->assertEquals('N', $decision);
	}

	public function testCRABucketIsEmpty()
	{
		$this->assertBucketEmpty('CRA');
	}

	public function testCRASegmentDecisionIsNull()
	{
		$decision = $this->_response->getSegmentDecision('CRA');
		$this->assertNull($decision);
	}

	public function testTLTBucketIsEmpty()
	{
		$this->assertBucketEmpty('TLT');
	}

	public function testTLTSegmentDecisionIsNull()
	{
		$decision = $this->_response->getSegmentDecision('TLT');
		$this->assertNull($decision);
	}

	protected function assertBucketContains($value, $bucket)
	{
		$buckets = $this->_response->getDecisionBuckets();
		$this->assertArrayHasKey($bucket, $buckets);
		$this->assertEquals($value, $buckets[$bucket]);
	}

	protected function assertBucketEmpty($bucket)
	{
		$buckets = $this->_response->getDecisionBuckets();
		$this->assertArrayNotHasKey($bucket, $buckets);
	}


	protected function getFixture($name)
	{
		$file = dirname(__FILE__).'/'.$name.'.xml';
		return file_get_contents($file);
	}
}

?>