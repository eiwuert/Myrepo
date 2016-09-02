<?php
/**
 * Unit tests for VendorAPI_CFE_Actions_AddPageToken
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_CFE_Actions_AddPageTokenTest extends PHPUnit_Framework_TestCase
{
	public function testAddData()
	{
		$action = $this->getMock('VendorAPI_CFE_Actions_AddPageToken',
			array('evalParameters'));
		$action->expects($this->once())->method('evalParameters')
			->with($this->isInstanceOf('ECash_CFE_IContext'))
			->will($this->returnValue(array("key1" => "value1", "key2" => "value2")));
		$context = $this->getMock('ECash_CFE_IContext');
		$array = new ArrayObject();
		$context->expects($this->once())->method('getAttribute')
			->with('page_data')->will($this->returnValue($array));
		$action->execute($context);
		$this->assertEquals("value1", $array["tokens"]["key1"]);
		$this->assertEquals("value2", $array["tokens"]["key2"]);
	}
}