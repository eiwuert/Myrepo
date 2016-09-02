#!/usr/bin/php
<?php

require_once('../code/ECashCra.php');

if ($_SERVER['argc'] >= 3)
{
	try
	{
		$date = date('Y-m-d', strtotime($_SERVER['argv'][1]));
		$driver = ECashCra::getDriver($_SERVER['argv'][2]);
	}
	catch (InvalidArgumentException $e)
	{
		die("Error: Could not load driver: {$e->getMessage()}\n");
	}
	$driver->handleArguments(array_slice($_SERVER['argv'], 3));
}
else
{
	die("\tUsage: " . basename(__FILE__) . " date driver_name [driver arguments]\n");
}

$cra_source = 'TSS';
$api = new ECashCra_Api(
	$driver->getCraApiConfig($cra_source,'url'),
	$driver->getCraApiConfig($cra_source,'username'),
	$driver->getCraApiConfig($cra_source,'password')
);
$script = new ECashCra_Scripts_ExportRecoveries($api);

$script->setExportDate($date);
$script->processApplications($driver);

?>