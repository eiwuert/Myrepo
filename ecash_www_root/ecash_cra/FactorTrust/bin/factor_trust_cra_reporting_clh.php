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

$cra_source = 'CLH-FT-1A';

$api = new ECashCra_Api(
	$driver->getCraApiConfig($cra_source,'url'),
	$driver->getCraApiConfig($cra_source,'username'),
	$driver->getCraApiConfig($cra_source,'password'),
	$driver->getCraApiConfig($cra_source,'store'),
	$driver->getCraApiConfig($cra_source,'merchant')
);

$script = new ECashCra_Scripts_NewLoansCLH($api);
$script->setExportDate($date);
$script->processApplications($driver);

$script = new ECashCra_Scripts_PaymentsCLH($api);
$script->setExportDate($date);
$script->processApplications($driver);

$script = new ECashCra_Scripts_PaymentsDueDateModCLH($api);
$script->setExportDate($date);
$script->processApplications($driver);

$script = new ECashCra_Scripts_RolloversCLH($api);
$script->setExportDate($date);
$script->processApplications($driver);

$script = new ECashCra_Scripts_RolloversDueDateModCLH($api);
$script->setExportDate($date);
$script->processApplications($driver);

$script = new ECashCra_Scripts_ReturnsCLH($api);
$script->setExportDate($date);
$script->processApplications($driver);

$script = new ECashCra_Scripts_ChargeoffsCLH($api);
$script->setExportDate($date);
$script->processApplications($driver);

$script = new ECashCra_Scripts_BankruptcyCLH($api);
$script->setExportDate($date);
$script->processApplications($driver);

$script = new ECashCra_Scripts_VoidsCLH($api);
$script->setExportDate($date);
$script->processApplications($driver);

?>
