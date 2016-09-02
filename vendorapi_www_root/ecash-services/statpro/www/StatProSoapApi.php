<?php

/**
 * The SOAP API for StatPro
 *
 * This API has been re-written to implement the StatPro client directly.
 * Each function wrapper expects an access key as the first parameter which
 * is used to identify the customer/environment. This information is used
 * to create a StatPro client and the corresponding client function is
 * called.
 *
 * @author Bryan Geraghty <bryan.geraghty@sellingsource.com>
 * @since 2008-04-15
 */

require_once('libolution/AutoLoad.1.php');

set_time_limit(0);
ignore_user_abort();

if (class_exists('SoapServer') === FALSE)
{
	throw new Exception(
		'PHP SOAP support does not appear to be enabled on this server'
	);
}
// end if // class_exists //

/**
 * Used by the soap server to deal with problems
 *
 * @param Exception $exception
 * @return void
 */
function handle_exception($exception)
{
	global $server;
	$server->fault($exception->getCode(), $exception->getMessage());
}
set_exception_handler('handle_exception');

/**
 * SOAP API Object
 *
 * @author Bryan Geraghty <bryan.geraghty@sellingsource.com>
 */
class StatProSoapApi // extends Stats_StatPro_Client_1
{

	/**
	 * The hash => array map used for identifying a customer/environment
	 *
	 * @var array
	 */
	protected $access_key_map = array(

		'some_license_key' => 
			array('mode' => 'live', 'user' => 'username', 'pass' => 'password'),
		'some_license_key2' => 
			array('mode' => 'test', 'user' => 'username', 'pass' => 'password'),
	);

	/**
	 * Creates a StatPro client object. Used by all of the functions
	 * in this API.
	 *
	 * @param string $access_key The hash value which will identify the
	 * customer/environment
	 * @return void
	 */
	protected function createClient($access_key)
	{
		if (array_key_exists($access_key, $this->access_key_map) === FALSE)
		{
			throw new Exception('Invalid access key');
		}
		
		$key_data = $this->access_key_map[$access_key];
		
		$statpro_key = 'spc_' . $key_data['user'] . '_' . $key_data['mode'];
		$auth_user = $key_data['user'];
		$auth_password = $key_data['pass'];
		
		$this->statpro = new Stats_StatPro_Client_1(
			$statpro_key, $auth_user, $auth_password
		);
	}
	// end function // createClient //
	
	/**
	 * Used for intergation testing, always returns TRUE
	 *
	 * @param string $access_key The hash value which will identify the
	 * customer/environment
	 * @return boolean Always returns TRUE
	 */
	public function testConnection($access_key)
	{
		$this->createClient($access_key);
		
		return TRUE;
	}
	// end function // testConnection //

	/**
	 * Generates a track key and inserts it into the database
	 *
	 * @param string $access_key The hash value which will identify the
	 * customer/environment
	 * @return string
	 */
	public function createTrackKey($access_key)
	{
		$this->createClient($access_key);
		
		$track_key = $this->statpro->createTrackKey();
		
		return($track_key);
	}
	// end function // createTrackKey //
	
	/**
	 * Generates a space key and inserts it into the database if it is not
	 * already there
	 *
	 * @param string $access_key The hash value which will identify the
	 * customer/environment
	 * @param array $space_definition An associative array containing the
	 * page_id, promo_id, and promo_sub_code for which the stat will be
	 * recorded
	 * @return string
	 */
	public function getSpaceKey($access_key, $space_definition)
	{
		$this->createClient($access_key);
		
		$space_definition = get_object_vars($space_definition);
		// file_put_contents('/tmp/StatProSoapApi.txt', print_r($space_definition, true));

		$space_key = $this->statpro->getSpaceKey($space_definition);
		
		return $space_key;
	}
	// end function // getSpaceKey //
	
	/**
	 * Records a statpro event
	 *
	 * @param string $access_key The hash value which will identify the
	 * customer/environment
	 * @param string $track_key
	 * @param string $space_key
	 * @param string $event_type_key
	 * @param int $date_occurred
	 * @param int $event_amount
	 * @return void
	 */
	public function recordEvent($access_key, $track_key, $space_key, $event_type_key,
			$date_occurred = NULL, $event_amount = NULL)
	{
		$this->createClient($access_key);
		
		$this->statpro->recordEvent(
			$track_key, $space_key, $event_type_key, $date_occurred
		);
		
		// $debug = $access_key . ' :: ' . $track_key . ' :: ' . $space_key .
		// ' :: ' .  $event_type_key . ' :: ' . $date_occurred;
		// return($debug);

		return(true);
	}
	// end function // recordEvent //
}
// end class // StatProSoapApi

$version = "";
$is_version2 = false;
if ($_GET['v'] == "2")
{
	$version = "_v2";
	$is_version2 = true;
}

$host = $_SERVER['HTTP_HOST'];
$https = !empty($_SERVER['HTTPS']);
$soap_url = ($https ? 'https://' : 'http://') . $host
	. '/StatProSoapApi.php' . ($is_version2 ? "?v=2" : "?v=1");


if (array_key_exists('wsdl', $_GET))
{
	$tokens = array('%%%soap_url%%%' => htmlentities($soap_url));
	$wsdl = file_get_contents('StatProSoapApi'.$version.'.wsdl');
	echo str_replace(array_keys($tokens), array_values($tokens), $wsdl);
}
else
{
	$dirname = dirname($_SERVER['SCRIPT_URI']);
	$server = new SoapServer($soap_url . '&wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));
	$server->setClass('StatProSoapApi');
	$server->handle();
}

?>
