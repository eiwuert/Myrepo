<?php
/**
 * Vendor API Soap Service
 * @author Jim Wu <jim.wu@sellingsource.com>
 */
// We have to setup the lib path for libolution before we can include the AutoLoad class.
file_put_contents('/tmp/vendorcall','blah');
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

if (!isset($_GET['enterprise'], $_GET['company']))
{
	Vendor_API_Fail(400, 'Company or Enterprise not specified');
}
else
{
	$enterprise = $_GET['enterprise'];
	$company = $_GET['company'];
	$host = $_SERVER['HTTP_HOST'];
	$https = !empty($_SERVER['HTTPS']);
	$soap_url = ($https ? 'https://' : 'http://') . $host
		. '/soap.php?enterprise=' . urlencode($enterprise) . '&company=' . urlencode($company);


	// Set Mode
	$mode = 'DEV';
	if (isset($_SERVER['ENVIRONMENT_MODE']))
	{
		$mode = $_SERVER['ENVIRONMENT_MODE'];
	}

	$loader = new VendorAPI_Loader($enterprise, $company, $mode);
	$loader->bootstrap();
	$driver = $loader->getDriver();
	$driver->use_bfw_prpc = true;

	if (array_key_exists('wsdl', $_GET))
	{
//		include('wsdl.php');
		$tokens = array('%%%soap_url%%%' => htmlentities($soap_url));
		$wsdl = file_get_contents('../code/VendorAPI/Service.wsdl');
		header('Content-Type: text/xml');
		echo str_replace(array_keys($tokens), array_values($tokens), $wsdl);
	}
	else
	{
		$soap = new SoapServer($soap_url . '&wsdl', array('cache_wsdl' => WSDL_CACHE_NONE, 'encoding' => 'ISO-8859-1'));
		try
		{
			$authenticator = $driver->getAuthenticator();
			if (!$authenticator->authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
			{
				header('WWW-Authenticate: Basic realm="Vendor API Soap Service"');
				header('HTTP/1.0 401 Unauthorized');
				echo 'This account does not have access to the vendor api soap service.';
				exit;
			}
			else
			{
				$call_context = new VendorAPI_CallContext();
				$call_context->setApiAgentName($_SERVER['PHP_AUTH_USER']);
				$call_context->setApiAgentId($authenticator->getAgentId());
				$call_context->setCompanyId($driver->getCompanyId());
				$call_context->setCompany($driver->getCompany());
				$service = new VendorAPI_Service($driver, $call_context, $request_log);
				$soap->setClass('VendorAPI_ServiceSoapWrapper', $service);
				$soap->handle();
			}
		}
		catch (Exception $e)
		{
			$soap->fault("Server", "Unexpected Error: {$e->getMessage()}");
		}
	}
}
?>
