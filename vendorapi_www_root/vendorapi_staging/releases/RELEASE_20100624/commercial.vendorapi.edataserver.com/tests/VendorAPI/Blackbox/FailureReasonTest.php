<?php 

class VendorAPI_Blackbox_FailureReasonTest extends PHPUnit_Framework_TestCase
{
	protected $reason;
	public function setup()
	{
		$this->reason = new VendorAPI_Blackbox_FailureReason();
	}
	
	public function tearDown()
	{
		
	}
	
	public function test0()
	{
		$this->assertEquals("Oh No", $this->reason->comment("Oh No"));
		$this->assertEquals("Oh No", $this->reason->comment());
	}
	
	public function test01()
	{
		$this->assertEquals("oh_no", $this->reason->short("oh_no"));
		$this->assertEquals("oh_no", $this->reason->short());
	}
	
	public function test10()
	{
		$reason = new VendorAPI_Blackbox_FailureReason('oh_no', 'comment');
		$this->assertEquals("oh_no", $reason->short());
		$this->assertEquals("comment", $reason->comment());
	}
}