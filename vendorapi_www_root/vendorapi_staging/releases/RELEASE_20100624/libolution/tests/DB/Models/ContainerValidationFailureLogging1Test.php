<?php
/**
 * DB_Models_ContainerValidationFailureLogging_1 test case.
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DB_Models_ContainerValidationFailureLogging1Test extends PHPUnit_Framework_TestCase
{
	
	/**
	 * Tests ECash_Models_ContainerValidationFailureLogging_1->update()
	 * @return void
	 */
	public function testUpdate()
	{
		$exceptions = array();
		for ($i = 0; $i < 10; $i++)
		{
			$exceptions[] = new Exception();
		}
		$log = $this->getMock("Applog", array("Write"));
		$log->expects($this->exactly(count($exceptions)))
			->method("Write");
		$observer = new DB_Models_ContainerValidationFailureLogging_1($log);
		$observed = $this->getMock("DB_Models_IContainer_1");
		$observed->expects($this->once())
			->method("getValidationExceptionStack")
			->will($this->returnValue($exceptions));
		$observer->update($observed);
	}

}

