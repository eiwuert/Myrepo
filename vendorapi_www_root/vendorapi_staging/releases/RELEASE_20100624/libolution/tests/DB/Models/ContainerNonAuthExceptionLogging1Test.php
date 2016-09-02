<?php
/**
 * DB_Models_ContainerNonAuthExceptionLogging_1 test case.
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DB_Models_ContainerNonAuthExceptionLogging1Test extends PHPUnit_Framework_TestCase
{
	
	/**
	 * Tests DB_Models_ContainerNonAuthExceptionLogging_1->update()
	 * @return void
	 */
	public function testUpdate()
	{
		$exception = new Exception();

		$log = $this->getMock("Applog", array("Write"));
		$log->expects($this->once())->method("Write");
		$observer = new DB_Models_ContainerNonAuthExceptionLogging_1($log);
		$observed = $this->getMock("DB_Models_IContainer_1");
		$observed->expects($this->once())
			->method("getNonAuthoritativeModelException")
			->will($this->returnValue($exception));
		$observer->update($observed);
	}

}

