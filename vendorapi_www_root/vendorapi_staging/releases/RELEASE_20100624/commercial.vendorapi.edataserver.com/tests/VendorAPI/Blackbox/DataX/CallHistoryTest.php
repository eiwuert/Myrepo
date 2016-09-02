<?php

class VendorAPI_Blackbox_DataX_CallHistoryTest extends PHPUnit_Framework_TestCase
{
	protected $_call1;
	protected $_call2;
	protected $_history;

	public function setUp()
	{
		$this->_call1 = new TSS_DataX_Result('test1', 0, '', '', NULL);

		$this->_history = new VendorAPI_Blackbox_DataX_CallHistory();
		$this->_history->addResult($this->_call1);
	}

	public function tearDown()
	{
		$this->_call1 = null;
		$this->_call2 = null;
		$this->_history = null;
	}

	public function testHasResultReturnsTrueForCallsInHistory()
	{
		$this->assertTrue($this->_history->hasResult('test1'));
	}

	public function testHasResultRetursnFalseForCallsNotInHistory()
	{
		$this->assertFalse($this->_history->hasResult('test4'));
	}

	public function testGetResultReturnsResultAdded()
	{
		$r = $this->_history->getResult('test1');
		$this->assertSame($this->_call1, $r);
	}

	public function testGetResultReturnsNullForCallsNotInHistory()
	{
		$this->assertNull($this->_history->getResult('test4'));
	}

	public function testIteratorContainsOnlyCallsAdded()
	{
		$i = $this->_history->getIterator();
		$actual = array(); foreach ($i as $r) $actual[] = $r;

		$this->assertEquals(array($this->_call1), $actual);
	}
}

?>
