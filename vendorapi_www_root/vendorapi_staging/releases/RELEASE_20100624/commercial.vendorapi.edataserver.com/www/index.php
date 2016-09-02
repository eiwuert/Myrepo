<?php
/**
 * Vendor API Server Connection
 * @author Raymond Lopez <raymond.lopez@selingsource.com>
 */

// We have to setup the lib path for libolution before we can include the AutoLoad class.
define('VENDORAPI_BASE_DIR', realpath(dirname(__FILE__) . '/../'));
set_include_path(
	'.'
	. PATH_SEPARATOR . VENDORAPI_BASE_DIR . '/lib/'
	. PATH_SEPARATOR . '/virtualhosts/'
	. PATH_SEPARATOR . '/usr/share/php'
);

require_once 'libolution/AutoLoad.1.php';
AutoLoad_1::addSearchPath(
	'../code/'
);

$fp = fopen('/var/log/vendor_api/request', 'a');
if ($fp !== false) {
	$request_log = new Log_StreamLogger_1($fp);
} else {
	$request_log = null;
}

if (!function_exists('Vendor_API_Fail'))
{
	function Vendor_API_Fail($error_code, $error_message)
	{
		header("HTTP/1.1 {$error_code} {$error_message}");
		die($error_message);
	}
}

// Verify inputs
if (!isset($_REQUEST['enterprise'], $_REQUEST['company']))
{
	Vendor_API_Fail(400, 'Bad Request');
}
else
{
	$enterprise = $_REQUEST['enterprise'];
	$company = $_REQUEST['company'];
	// Set Mode
	$mode = 'DEV';
	if (isset($_SERVER['ENVIRONMENT_MODE']))
	{
		$mode = $_SERVER['ENVIRONMENT_MODE'];
	}

	$loader = new VendorAPI_Loader($enterprise, $company, $mode);
	try {

		$loader->bootstrap();
		$driver = $loader->getDriver();
		$driver->use_bfw_prpc = TRUE;
		$authenticator = $driver->getAuthenticator();
		/* @var $authenticator VendorAPI_IAuthenticator */

		if (!$authenticator->authenticate($_REQUEST['username'], $_REQUEST['password']))
		{
			Vendor_API_Fail(401, 'Unauthorized');
		}
		else
		{
			$call_context = new VendorAPI_CallContext();
            $call_context->setApiAgentName($_REQUEST['username']);
			$call_context->setApiAgentId($authenticator->getAgentId());
			$call_context->setCompanyId($driver->getCompanyId());
			$call_context->setCompany($driver->getCompany());
			$api = new VendorAPI_Service($driver, $call_context, $request_log);
			new VendorAPI_RPC_Server($api, $loader->getLog());
		}
	}
	catch (Exception $e)
	{
		$loader->getLog()->write(
			"Framework Error: {$e->getMessage()}\nTRACE: {$e->getTraceAsString()}",
			Log_ILog_1::LOG_CRITICAL
		);
	}
}

?>
