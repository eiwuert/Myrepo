<?php
require_once('test_setup.php');

class ECash_FactoryTest
{
	public function testGetFactory()
	{
		$db_config = $this->getMock('DB_IConnection_1');
		$factory = ECash_Factory::getFactory(dirname(__FILE__).'/_fixture/custdir/', 'test', $db_config);
	}
}
?>