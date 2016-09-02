<?php

require_once('ECashCra/Scripts/TestAbstract.php');

class ECashCra_Scripts_UpdatePaymentsTest extends ECashCra_Scripts_TestAbstract
{
	protected function getScriptName()
	{
		return 'ECashCra_Scripts_UpdatePayments';
	}
	
	protected function setUpDriverMocks($driver, $application)
	{
		$driver->expects($this->once())
			->method('getPayments')
			->with($this->equalTo('2008-03-21'))
			->will($this->returnValue(array(
				$application->getPayment()
		)));
	}
	
	protected function getExternalId($application)
	{
		return $application->getPayment()->getId();
	}
}

?>