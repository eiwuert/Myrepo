<?php
/** Tests VendorAPI Blackbox Target.
 *
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 */
class VendorAPI_Blackbox_TargetTest extends PHPUnit_Framework_TestCase
{
	protected $target;
	
	protected function setUp()
	{
		$data = new VendorAPI_Blackbox_StateData();
		$data->customer_history = new ECash_CustomerHistory();
		$this->target = new TargetTest($data);
	}
	
	protected function tearDown()
	{
		$this->target = NULL;
	}
	
	public function testGetWinner()
	{

		$data = new VendorAPI_Blackbox_Data();
		$result = $this->target->testGetWinner($data);

		$this->assertThat($result, $this->isinstanceOf('VendorAPI_Blackbox_Winner'));

	}
}

class TargetTest extends VendorAPI_Blackbox_Target
{
	/*
	 * Wrapper to test Get Winner
	 */
	public function testGetWinner(Blackbox_Data $data)
	{
		return $this->getWinner($data);
	}
	
}
