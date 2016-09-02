<?php 

class VendorAPI_Blackbox_TriggersTest extends PHPUnit_Framework_TestCase
{
	protected $triggers;
	
	public function setUp()
	{
		$this->triggers = new VendorAPI_Blackbox_Triggers();
	}
	
	public function tearDown()
	{
		$this->triggers = FALSE;
	}
	
	public function testCount()
	{
		$this->assertEquals(0, count($this->triggers));
	}
	
	public function testAddTrigger()
	{
		$this->triggers->addTrigger(new VendorAPI_Blackbox_Trigger("hello", "mom"));
		$this->assertEquals(1, count($this->triggers));
	}
	
	public function testIterator()
	{
		$this->triggers->addTrigger(new VendorAPI_Blackbox_Trigger("Hello"));
		$this->triggers->addTrigger(new VendorAPI_Blackbox_Trigger("Hi"));
		$i = 0;
		foreach ($this->triggers as $trigger)
		{
			$i++;
		}
		$this->assertEquals(2, $i);
	}
}