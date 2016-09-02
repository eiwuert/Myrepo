<?php


class VendorAPI_CFE_Actions_AddRequiredDataTest extends PHPUnit_Framework_TestCase
{
	public function testAddData()
	{
		$action = $this->getMock('VendorAPI_CFE_Actions_AddRequiredData', array('evalParameters'));
		$action->expects($this->once())->method('evalParameters')
			->with($this->isInstanceOf('ECash_CFE_IContext'))
			->will($this->returnValue("references"));
		$context = $this->getMock('ECash_CFE_IContext');
		$array = new ArrayObject();
		$context->expects($this->once())->method('getAttribute')
			->with('page_data')->will($this->returnValue($array));
		$action->execute($context);
		$this->assertTrue(in_array('references', (array)$array["required_data"]));

	}
}