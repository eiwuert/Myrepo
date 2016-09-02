<?php

class AALM_DataX_Responses_Perf_PassTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var AALM_DataX_Responses_Perf
	 */
	protected $_response;

	public function setUp()
	{
		$this->_response = new AALM_DataX_Responses_Perf();
		$this->_response->parseXML($this->getFixture('aalm_pass'));
	}

	public function testIsValidIsTrue()
	{
		$this->assertTrue($this->_response->isValid());
	}

	public function testDecisionIsYes()
	{
		$this->assertEquals('Y', $this->_response->getDecision());
	}

	public function testIDVBucketContainsA1()
	{
		$this->assertBucketContains('A1', 'IDV');
	}

	public function testIsIDVFailureIsFalse()
	{
		$this->assertFalse($this->_response->isIDVFailure());
	}

	public function testIDVSegmentDecisionIsYes()
	{
		$decision = $this->_response->getSegmentDecision('IDV');
		$this->assertEquals('Y', $decision);
	}

	public function testCRABucketContainsA1()
	{
		$this->assertBucketContains('A1', 'CRA');
	}

	public function testCRASegmentDecisionIsYes()
	{
		$decision = $this->_response->getSegmentDecision('CRA');
		$this->assertEquals('Y', $decision);
	}

	public function testTLTBucketContainsA1()
	{
		$this->assertBucketContains('A1', 'TLT');
	}

	public function testTLTSegmentDecisionIsYes()
	{
		$decision = $this->_response->getSegmentDecision('TLT');
		$this->assertEquals('Y', $decision);
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