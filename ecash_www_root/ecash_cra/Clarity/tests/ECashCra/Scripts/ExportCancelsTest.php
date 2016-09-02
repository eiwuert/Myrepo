<?php

require_once('ECashCra/Scripts/TestAbstract.php');

class ECashCra_Scripts_ExportCancelsTest extends ECashCra_Scripts_TestAbstract
{
	protected function getScriptName()
	{
		return 'ECashCra_Scripts_ExportCancels';
	}
	
	protected function setUpDriverMocks($driver, $application)
	{
		$driver->expects($this->once())
			->method('getCancellations')
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