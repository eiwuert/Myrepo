<?php

require_once('test_setup.php');
require_once('ECashCra/Packet/ApplicationHelper.php');

abstract class ECashCra_Scripts_TestAbstract extends PHPUnit_Framework_TestCase
{
	/**
	 * @var ECashCra_Api
	 */
	protected $mock_api;
	
	/**
	 * @var ECashCra_Scripts_ExportCancels
	 */
	protected $script;
	
	/**
	 * @var ECashCra_PacketResponse_UpdateResponse
	 */
	protected $response;
	
	public function setUp()
	{
		$this->response = $this->getMock('ECashCra_PacketResponse_UpdateResponse', array('isSuccess'));
		
		$this->mock_api = $this->getMock('ECashCra_Api', array(), array('http://test', 'user', 'pass'));
		
		$this->script = $this->getMock($this->getScriptName(), array('createResponse', 'logMessage'), array($this->mock_api));
		$this->script->setExportDate('2008-03-21');
		
		$this->script->expects($this->once())
			->method('createResponse')
			->will($this->returnValue($this->response));
	}
	
	public function testProcessApplications()
	{
		$application = new Tests_ApplicationHelper();
		
		$driver = $this->getMock('ECashCra_IDriver');
		
		$this->setUpDriverMocks($driver, $application);
		
		$this->response->expects($this->any())
			->method('isSuccess')
			->will($this->returnValue(true));
		
		$this->script->expects($this->once())
			->method('logMessage')
			->with(
				$this->equalTo(true), 
				$this->equalTo($this->getExternalId($application)),
				$this->response
			);
		
		$this->script->processApplications($driver);
	}
	
	abstract protected function getScriptName();
	
	abstract protected function setUpDriverMocks($driver, $application);
	
	abstract protected function getExternalId($application);
}

?>