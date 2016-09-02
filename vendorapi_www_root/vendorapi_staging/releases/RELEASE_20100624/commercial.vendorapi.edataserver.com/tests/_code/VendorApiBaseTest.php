<?php

abstract class VendorApiBaseTest extends PHPUnit_Framework_TestCase
{
	public function setGetApplicationExpectation(VendorAPI_IApplicationFactory $app_factory, $test_app_id, $app_returned)
	{
		$app_factory->expects($this->once())->method('getApplication')
			->with($test_app_id, $this->isInstanceOf('VendorApi_StateObjectPersistor'), $this->isInstanceOf('VendorAPI_StateObject'))
			->will($this->returnValue($app_returned));

	}
}

?>
