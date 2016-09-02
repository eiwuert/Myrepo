<?php

class VendorAPI_DB_FailoverConfigTest extends PHPUnit_Framework_TestCase
{
	protected $_config1;
	protected $_config2;
	protected $_db;
	protected $_failover;

	protected function setUp()
	{
		$this->_config1 = $this->getMock('DB_IDatabaseConfig_1', array('getConnection'));
		$this->_config2 = $this->getMock('DB_IDatabaseConfig_1', array('getConnection'));
		$this->_db = $this->getMock('DB_IConnection_1');

		$this->_failover = new DB_FailoverConfig_1();
		$this->_failover->addConfig($this->_config1);
		$this->_failover->addConfig($this->_config2);
	}

	protected function tearDown()
	{
		$this->_config1 = NULL;
		$this->_config2 = NULL;
		$this->_db = NULL;
		$this->_failover = NULL;
	}

	public function testWhenFirstSucceedsSecondNotAttempted()
	{
		$this->_config1->expects($this->atLeastOnce())
		->method('getConnection')
		->will($this->returnValue($this->_db));

		$this->_config2->expects($this->never())
		->method('getConnection');

		$this->_failover->getConnection();
	}

	public function testWhenFirstFailsSecondIsAttempted()
	{
		$this->_config1->expects($this->atLeastOnce())
		->method('getConnection')
		->will($this->throwException(new Exception()));

		$this->_config2->expects($this->atLeastOnce())
		->method('getConnection')
		->will($this->returnValue($this->_db));

		$this->_failover->getConnection();
	}

	public function testWhenAllFailThrowsException()
	{
		$this->_config1->expects($this->atLeastOnce())
		->method('getConnection')
		->will($this->throwException(new Exception()));

		$this->_config2->expects($this->atLeastOnce())
		->method('getConnection')
		->will($this->throwException(new Exception()));

		$this->setExpectedException('Exception');
		$this->_failover->getConnection();
	}

	/**
	 * Unfortunately, DB_Database_1::connect is marked as final,
	 * so we can't simply use a mock object and ensure that it gets
	 * called. Instead, we use a real database connection and check
	 * that getIsConnected (which returns the true state of the
	 * connection) is TRUE after returning from the config.
	 */
	public function testConnectsDBDatabase1()
	{
		$config = new DB_SQLiteConfig_1(':memory:');

		$failover = new DB_FailoverConfig_1();
		$failover->addConfig($config);

		/* @var $db DB_Database_1 */
		$db = $failover->getConnection();
		$this->assertTrue($db->getIsConnected());
	}
}

?>
