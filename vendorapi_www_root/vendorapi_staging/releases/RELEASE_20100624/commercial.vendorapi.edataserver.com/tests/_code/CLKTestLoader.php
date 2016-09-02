<?php
class CLKTestLoader extends ECash_VendorAPI_Loader
{
	public function getDriver()
	{
		$test_config = getTestDatabaseConfig();
		foreach ($GLOBALS['COMPANY_DATABASES'] as $name=>&$config)
		{
			$config = $test_config;
		}
		return parent::getDriver();
	}
}
?>