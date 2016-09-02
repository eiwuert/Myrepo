<?php

require_once('test_setup.php');

class ECashCraTest extends PHPUnit_Framework_TestCase
{
	public function testGetDriver()
	{
		$driver_name = uniqid('Dr');
		$class_name = 'ECashCra_Driver_'.$driver_name;

		$this->getMock('ECashCra_IDriver', array(), array(), $class_name);
		
		$this->assertThat(
			ECashCra::getDriver($driver_name),
			$this->isInstanceOf($class_name)
		);
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testBadGetDriver()
	{
		ECashCra::getDriver('DriverThatDoesntExist');
	}
}
?>