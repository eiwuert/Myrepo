<?php

require_once('ECashCra/Scripts/TestAbstract.php');

class ECashCra_Scripts_UpdateStatusesTest extends ECashCra_Scripts_TestAbstract
{
	public static function processApplicationDataProvider()
	{
		return array(
			array(ECashCra_Scripts_UpdateStatuses::STATUS_CHARGEOFF),
			array(ECashCra_Scripts_UpdateStatuses::STATUS_CLOSED),
			array(ECashCra_Scripts_UpdateStatuses::STATUS_FULL_RECOVERY),
		);
	}
	
	/**
	 * @dataProvider processApplicationDataProvider
	 */
	public function testProcessApplications($status)
	{
		$application = new Tests_ApplicationHelper();
		
		$driver = $this->getMock('ECashCra_IDriver');
		
		$this->setUpDriverMocks($driver, $application);
		$this->setUpStatusTranslation($driver, $status);
		
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
	
	public function testProcessApplicationsBadStatus()
	{
		$application = new Tests_ApplicationHelper();
		
		$driver = $this->getMock('ECashCra_IDriver');
		
		$this->setUpDriverMocks($driver, $application);
		$this->setUpStatusTranslation($driver, 'bad status');
		
		$this->response->expects($this->any())
			->method('isSuccess')
			->will($this->returnValue(true));
		
		$this->script->expects($this->never())
			->method('logMessage')
			->withAnyParameters();
		
		$this->script->processApplications($driver);
	}
	
	protected function getScriptName()
	{
		return 'ECashCra_Scripts_UpdateStatuses';
	}
	
	protected function setUpDriverMocks($driver, $application)
	{
		$driver->expects($this->once())
			->method('getStatusChanges')
			->with($this->equalTo('2008-03-21'))
			->will($this->returnValue(array(
				$application->getApplication()
		)));
	}
	
	protected function setUpStatusTranslation($driver, $status)
	{
		$driver->expects($this->once())
			->method('translateStatus')
			->will($this->returnValue($status));
	}
	
	protected function getExternalId($application)
	{
		return $application->getApplication()->getApplicationId();
	}
}

?>