<?php
/**
 * DB_Models_SynchronizedKeyContainer_1 test case.
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DB_Models_SynchronizedKeyContainer_1Test extends DB_Models_Container1Test
{
	public function providerSynchronizedCalls()
	{
		return array(
			array("insert"),
			array("update"),
			array("save"),
		);
	}
	
	/**
	 * Tests DB_Models_SynchronizedKeyContainer_1->insert()
	 * 
	 * @param string $method Method to test for synchronization
	 * @dataProvider providerSynchronizedCalls
	 */
	public function testSynchronizedCalls($method)
	{
		$key = array("key1", "key2");
		
		$auth = $this->getMock("DB_Models_Container_1");
		$auth->expects($this->once())
			->method("getPrimaryKey")
			->will($this->returnValue($key));
		$auth->expects($this->any())
			->method("__get")
			->will($this->returnArgument(0));
		$auth->expects($this->once())
			->method($method);

		$non_auth = $this->getMock("DB_Models_Container_1");
		$non_auth->expects($this->exactly(2))
			->method("__set");
		$non_auth->expects($this->once())
			->method($method);

		$container = new DB_Models_SynchronizedKeyContainer_1(TRUE);
		$container->setAuthoritativeModel($auth);
		$container->addNonAuthoritativeModel($non_auth);
		call_user_func_array(array($container, $method), array());
	}
	
}

