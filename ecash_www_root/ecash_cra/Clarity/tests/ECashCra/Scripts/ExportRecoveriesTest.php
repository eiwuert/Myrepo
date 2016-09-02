<?php

require_once('ECashCra/Scripts/TestAbstract.php');

class ECashCra_Scripts_ExportRecoveriesTest extends ECashCra_Scripts_TestAbstract
{
	public function testProcessApplications()
	{
		$application = new Tests_ApplicationHelper();
		
		$driver = $this->getMock('ECashCra_IDriver');
		$driver->expects($this->once())
			->method('getRecoveries')
			->with($this->equalTo('2008-03-21'))
			->will($this->returnValue(array(
				$application->getApplication()
		)));
		
		$this->response->expects($this->any())
			->method('isSuccess')
			->will($this->returnValue(true));
		
		$this->script->expects($this->once())
			->method('logMessage')
			->with(
				$this->equalTo(true), 
				$this->equalTo($application->getApplication()->getApplicationId()),
				$this->response
			);
		
		$this->script->processApplications($driver);
	}
	
	protected function getScriptName()
	{
		return 'ECashCra_Scripts_ExportRecoveries';
	}
	
	protected function setUpDriverMocks($driver, $application)
	{
		$driver->expects($this->once())
			->method('getRecoveries')
			->with($this->equalTo('2008-03-21'))
			->will($this->returnValue(array(
				$application->getApplication()
		)));
	}
	
	protected function getExternalId($application)
	{
		return $application->getApplication()->getApplicationId();
	}
}

?>