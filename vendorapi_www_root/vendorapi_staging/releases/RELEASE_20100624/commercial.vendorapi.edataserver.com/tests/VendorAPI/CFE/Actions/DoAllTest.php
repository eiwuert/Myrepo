<?php
/**
 * Unit tests for VendorAPI_CFE_Actions_DoAll
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_CFE_Actions_DoAllTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var ECash_CFE_IContext
	 */
	private $context;

	/**
	 * set up for test
	 */
	public function setUp()
	{
		$this->context = $this->getMock('ECash_CFE_IContext');
	}

	/**
	 * Verify it won't blow up with no params
	 */
	public function testNoParams()
	{
		$action = new VendorAPI_CFE_Actions_DoAll(array());
		$action->execute($this->context);
	}
	
	/**
	 * Verify it will work with one param
	 */
	public function testOneParam()
	{
		$sub = $this->getMock('ECash_CFE_IAction');
		$sub->expects($this->once())->method('execute')->with($this->equalTo($this->context));
		$action = new VendorAPI_CFE_Actions_DoAll(array($sub));
		$action->execute($this->context);
	}
		
	/**
	 * Verify it will work with one param
	 */
	public function testMultipleParams()
	{
		$sub = $this->getMock('ECash_CFE_IAction');
		$sub->expects($this->exactly(4))->method('execute')->with($this->equalTo($this->context));
		$action = new VendorAPI_CFE_Actions_DoAll(array($sub, $sub, $sub, $sub));
		$action->execute($this->context);
	}
}