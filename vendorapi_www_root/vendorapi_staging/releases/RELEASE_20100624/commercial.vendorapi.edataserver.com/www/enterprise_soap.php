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


if (!function_exists('Vendor_API_Fail'))
{
	function Vendor_API_Fail($error_code, $error_message)
	{
		header("HTTP/1.1 {$error_code} {$error_message}");
		die($error_message);
	}
}

// Verify inputs
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
	$soap_path = ($https ? 'https://' : 'http://') . $host
		. '/enterprise_soap.php?enterprise=' . urlencode($enterprise) . '&company=' . urlencode($company);


	// Set Mode
	$mode = 'DEV';
	if (isset($_SERVER['ENVIRONMENT_MODE']))
	{
		$mode = $_SERVER['ENVIRONMENT_MODE'];
	}

	$loader = new VendorAPI_Loader($enterprise, $company, $mode);
	$loader->bootstrap();
	$driver = $loader->getDriver();

	if (array_key_exists('wsdl', $_GET))
	{
		echo $driver->getEnterpriseSoapWsdl($soap_path);
	}
	else
	{
		$soap = new SoapServer($soap_path . '&wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));
		try {
			$authenticator = $driver->getAuthenticator();
			/* @var $authenticator VendorAPI_IAuthenticator */

			if (!$authenticator->authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], 'vendor_api_esoap'))
			{
			    header('WWW-Authenticate: Basic realm="Vendor Enterprise Soap API"');
			    header('HTTP/1.0 401 Unauthorized');
			    echo 'This account does not have access to the enterprise soap api.';
			    exit;
			}
			else
			{
				$call_context = new VendorAPI_CallContext();
				$call_context->setApiAgentId($authenticator->getAgentId());
				$call_context->setCompanyId($driver->getCompanyId());
				$call_context->setCompany($driver->getCompany());
				
				$soap->setClass('VendorAPI_EnterpriseService', $driver, $call_context);
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
