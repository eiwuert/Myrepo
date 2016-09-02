<?php 

class VendorAPI_Blackbox_TriggerTest extends PHPUnit_Framework_TestCase
{
	protected $trigger;
	
	public function setUp()
	{
		$this->trigger = new VendorAPI_Blackbox_Trigger('trigger', 'stat');
	}
	
	public function tearDown()
	{
		unset($this->trigger);
	}
	
	public function testHit()
	{
		$this->assertFalse($this->trigger->isHit());
		$this->trigger->hit();
		$this->assertTrue($this->trigger->isHit());
		$this->trigger->unhit();
		$this->assertFalse($this->trigger->isHit());
	}
	
	public function testGetAction()
	{
		$this->assertEquals('trigger', $this->trigger->getAction());
	}
	
	public function testSetAction()
	{
		$this->trigger->setAction('MyNewAction');
		$this->assertEquals('MyNewAction', $this->trigger->getAction());
	}
	
	public function testGetStat()
	{
		$this->assertEquals('stat', $this->trigger->getStat());
	}
	
	public function testSetStat()
	{
		$this->trigger->setStat('MyNewStat');
		$this->assertEquals('MyNewStat', $this->trigger->getStat());
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testSetActionInvalid()
	{
		$curl = curl_init();
		$this->trigger->setAction($curl);
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testSetStatInvalid()
	{
		$curl = curl_init();
		$this->trigger->setStat($curl);
	}

}