<?php

require_once 'autoload_setup.php';

class Object1Test extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->obj = $this->getMock(
			'Object_1',
			array('getBlah', 'setWoot')
		);
	}

	public function testGetCallsMethod()
	{
		$this->obj->expects($this->once())
			->method('getBlah');
		$this->obj->blah;
	}

	public function testGettingInvalidPropertyThrowsException()
	{
		$this->setExpectedException('InvalidPropertyException_1');
		$this->obj->ha;
	}

	public function testSetCallsMethod()
	{
		$this->obj->expects($this->once())
			->method('setWoot')
			->with('test');
		$this->obj->woot = 'test';
	}

	public function testSettingInvalidPropertyThrowsException()
	{
		$this->setExpectedException('InvalidPropertyException_1');
		$this->obj->nice = 'sassy';
	}

	public function testIssetReturnsTrueWhenMethodExists()
	{
		$this->assertTrue(isset($this->obj->blah));
	}

	public function testIssetReturnsFalseWhenMethodMissing()
	{
		$this->assertFalse(isset($this->obj->ha));
	}
}

?>