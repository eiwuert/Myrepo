<?php
/**
 * JSON Post based StatPro event processor
 * 
 * This service is documented in /index.php
 * Any changes to this service need to have the changes reflected in the
 * on-line documentation 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
require_once 'libolution/AutoLoad.1.php';
AutoLoad_1::addSearchPath('../../../code/');

$processor = new StatsService_MessageProcessor_Event();
$log = new Log_SysLog_1("StatProServiceJsonEvent");
$service = new StatsService_JSON($processor, $log);

try {
	$request = Site_Request::fromGlobals(TRUE);
	$response = $service->processRequest($request);
} catch (Exception $e) {
	$log->write("Error processing stat event post request: " . $e->getMessage());
	$response = new Site_Response_Http("Unknown error occured: ".$e->getMessage(), 500);
}

$response->render();
