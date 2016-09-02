<?php

require_once ('OLPBlackboxTestSetup.php');

class OLPBlackbox_Vetting_Factory_CollectionTest extends PHPUnit_Framework_TestCase
{
	public function testVettingFactory()
	{
		$db = TEST_DB_MYSQL4();
		$info = array();
		$info['db'] = TEST_GET_DB_INFO()->name;
		
		$factory = $this->getMock(
			'OLPBlackbox_Vetting_Factory_Collection',
			array('getDB', 'getDBInfo')
		);
		$factory->expects($this->any())
			->method('getDB')
			->will($this->returnValue($db));
		$factory->expects($this->any())
			->method('getDBInfo')
			->will($this->returnValue($info));
		
		$factory->getCollection();
	}
}

?>