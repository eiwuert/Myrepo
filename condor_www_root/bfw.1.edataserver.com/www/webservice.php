<?php

/**
 * TSS web services. It does following 2 jobs: handle SOAP requests, or show WSDL content.
 * 
 * == Create a new web service ==
 * Suppose the class name for the web service is 'FraudCheck'.
 *     *. The class file is named 'fraud_check.php' (lower case), and should be put in
 *        the same directory as this one.
 *     *. The wsdl file is named 'fraud_check.wsdl' (lower case), and should be put in 
 *        the same directory as this one.
 *     *. To access/call the web service, use URL like
 *        'http://bfw.1.edataserver.com/webservice.php?service=Fraud_Check'
 *        (Value for argument 'service' is case insensitive)
 * 
 * == Restrictions on class definition ==
 * If you want to use Demin's script to create a WSDL file for a PHP script (described in next
 * section, you have to follow these class definition restrictions:
 *     *. Only SOAP methods should be public; all other methods should be private.
 *     *. First non-empty line of the class file shuld only contain PHP start tag (<? or <?php).
 *     *. Don't use PHP end tag (?>) at the end of the class file.
 * 
 * == Create WSDL file ==
 * You can use PEAR package Services_Webservice to create WSDL files. Please see revision 
 * 15327 of this file (webservice.php) for basical usage of PEAR package Services_Webservice.
 * Please not that after the WSDL file is generated using PEAR::Services_Webservice, you need
 * to make minor changes on it to make it work in OLP (e.g., changing encoding type 'encoding' 
 * to 'literal', changing address location from http://bfw.1.edataserver.com.dsXX.tss:8080/xxx 
 * to http://bfw.1.edataserver.com/xxx, etc). Demin has written a script creating WSDL 
 * automatically base on the PEAR package.
 * 
 * But the best way to create a WSDL file from a PHP script is using Zend Eclipse's "Web 
 * Services support" feature. Here is the step by step instruction based on Zend Eclipse's 
 * Help documentation "Generating WSDL Files":
 *     *. Create the PHP file which includes all the services you want to include in your WSDL file.
 *     *. From the Menu Bar, go to File | Export | PHP | WSDL File
 *     *. Follow instructions in the Generate WSDL File dialog.
 *         **. In "Global Settings", you need to specify Namespace (we suggestion to use class 
 *             name), Binding Options (PRC - Oriented), and Encoding type (Use literal); and, 
 *             you'd better check the checkbox "Use default SOAP encoding style".
 *     *. After the WSDL file created, you have to open it, go to Property view for port and 
 *        change binding address there.
 *         ** A binding address is something like 
 *            http://bfw.1.edataserver.com/webservice.php?service=Fraud_Check
 *     *. Well, the last step is: test your WSDL file.
 * 
 * @author Demin Yin <Demin.Yin@SellingSource.com>
 * @since  Tue 11 Dec 2007 09:53:28 AM PST
 * @see    GForge #3837 - API for Fraud Scan
 * @see    GForge #3838 - NEW PROJECT - API for Frequency Score 
 * @see    http://olp.jubilee.tss/index.php/OLP_Web_Services OLP Web Services
 * @see    http://pear.php.net/package/Services_Webservice PEAR package Services_Webservice
 */

define('RETURN_CSV_STRING', 1);
define('RETURN_JSON_STRING', 2);
define('RETURN_PHP_SERIALIZE_STRING', 3);

include_once './config.php';

if (!defined('DEBUG'))
{
	define('DEBUG', FALSE);
}

include_once BFW_CODE_DIR . 'setup_db.php';

include_once BFW_CODE_DIR . 'Memcache_Singleton.php';
include_once BFW_CODE_DIR . 'Cache_Config.php';

include_once 'data_validation.2.php'; // used to validate email address.

/**
 * This is the parent class of many new BFW web services, including the API for 
 * Fraud Scan, and the API for Frequency Scoring.
 * 
 * * You can read following code to learn how to create a new web service using 
 *   this parent class:
 *       ./fraud_check.php
 *       ./frequency_score.php
 * * In parent class (OLPWebService): Forsecurity reason, it's not recommended 
 *   to put public methods in this parent class. If you have to add a public 
 *   method in it, make sure the method is safe enough for public access.
 * * In child class: For security reason, only declare a method public if it 
 *   should be accessable via SOAP requests.
 * 
 * @author Demin Yin <Demin.Yin@SellingSource.com>  
 */
class OLPWebService
{	
	/**
	 * A list of web serivces (names are in lower case).
	 * This might be the ONLY place needed to be changed when adding a new web service.
	 * 
	 * @var array
	 */
	private static $service_names = array(
		'fraud_check',
		'frequency_score',
	);
	
	/**
	 * Types of the string returned for API calls.
	 * 
	 * A string returned could be in one of the following types:
	 *     * in CSV format (data separated by commas);
	 *     * in Json format;
	 *     * in serialized format (used by PHP language only).
	 * 
	 * @var array
	 */
	protected $return_types = array(
		RETURN_CSV_STRING,
		RETURN_JSON_STRING,
		RETURN_PHP_SERIALIZE_STRING,
	);
	
	/**
	 * @var MySQL_Wrapper
	 */
	protected $sql;
	
	/**
	 * @var string database name
	 */
	protected $database;
	
	/**
	 * An empty constructor.
	 */
	public function __construct()
	{
	}
	
	/**
	 * SOAP handler. This is the entry point of the SOAP API.
	 * 
	 * @return string A return message.
	 */
	public static function runSoapHandler()
	{
		$service_name = strtolower($_REQUEST['service']);
		// Name conversion for classes: Fraud_Check (service name) => FraudCheck (class name) 
		$class_name = preg_replace('/[^a-zA-Z0-9]/', '', $service_name);
		// Name conversion for class file: Fraud_Check (service name) => fraud_check.php  (file name)
		$class_file_name = './' . strtolower($service_name) . '.php';
		// Name conversion for wsdl file:  Fraud_Check (service name) => fraud_check.wsdl (file name)
		$wsdl_file_name = './' . strtolower($service_name) . '.wsdl';
		
		if (isset($_SERVER['HTTP_SOAPACTION'])) // handler SOAP requests. 
		{
			switch (TRUE)
			{
				case (!in_array($service_name, self::$service_names)):
					OLPWebService_Message::setErrorMessageName('INVALID_WEB_SERVICE_NAME');
					$class_name = 'OLPWebService_EmptyService';
					break;
				case (!is_file($class_file_name)):
					OLPWebService_Message::setErrorMessageName('NON_IMPLEMENTED_SERVICE');
					$class_name = 'OLPWebService_EmptyService';
					break;
				default: // no any error found.
					include_once $class_file_name;
					break;
			}
			
			$soap_server_options = array(
				'uri'          => $service_name, 
				'encoding'     => SOAP_LITERAL,
				'soap_version' => SOAP_1_2,
			);

			$server = new SoapServer(NULL, $soap_server_options);
			$server->SetClass($class_name);
			$server->handle();
		}
		else // show WSDL file content. 
		{
			// If an error occurs here, a plain error message (which is not a valid XML/WSDL doc) will be 
			// returned; otherwise, a valid WSDL document will be returned.
			

			switch (TRUE)
			{
				case (!in_array($service_name, self::$service_names)):
					exit(OLPWebService_Message::getMessage('INVALID_WEB_SERVICE_NAME'));
					break;
				case (!is_file($wsdl_file_name)):
					exit(OLPWebService_Message::getMessage('WSDL_FILE_NOT_EXIST'));
					break;
				default:
					break;
			}
			
			header('Content-Type: text/xml');
			
			// Don't use $_SERVER['HTTP_HOST'] here coz it may not contain port number.
			if (intval($_SERVER['SERVER_PORT']) == 80)
			{
				$server_name = $_SERVER['SERVER_NAME'];
			}
			else
			{
				$server_name = $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];
			}
			
			$wsdl = file_get_contents($wsdl_file_name);
			$wsdl = preg_replace('/\/[^\/]*bfw\.1\.edataserver\.com[^\/]*\//i', "/{$server_name}/", $wsdl); // change URI for SOAP Server.
			echo trim($wsdl);
		}
	}
	
	/**
	 * Check if the remote request has permission to access this web service or not.
	 * 
	 * @param string $license_key License key.
	 * @param string $promo_id Promo ID.
	 * @param string $promo_sub_code Promo sub code.
	 * @return boolean|string Return TRUE if license key/promo id are valid; otherwise, return an error message.
	 */
	protected function hasAccessPermission($license_key, $promo_id, $promo_sub_code = '')
	{
		if (!is_numeric($promo_id) || (intval($promo_id) <= 10000))
		{
			return OLPWebService_Message::getMessage('INVALID_FORMAT_OF_PROMO_ID');
		}
		
		try //check license key 
		{
			$sql = $this->getConnection('management');
			$config_obj = new Cache_Config($sql);
			$config = $config_obj->Get_Site_Config($license_key, $promo_id, $promo_sub_code);
		}
		catch (Exception $e) // license key not found 
		{
			$config = NULL;
		}
		
		if (empty($config)) // License key is invalid. 
		{
			return OLPWebService_Message::getMessage('INVALID_LICENSE_KEY');
		}
		
		if (strcasecmp($config->promo_status->valid, 'valid') !== 0)
		{
			return OLPWebService_Message::getMessage('INVALID_PROMO_ID');
		}
		
		return TRUE;
	}
	
	/**
	 * Check if given email address is valid or not.
	 * 
	 * @param string $email Email address.
	 * @param int $max_length Maximum allowed length of email addresses.
	 * @return boolean|string Return TRUE if given email is valid; otherwise, return an error message.
	 */
	protected function hasValidEmail($email, $max_length = 0)
	{
		// Following email pattern was originally created by Zrekam makerZ, and shared at
		// http://regexlib.com/REDetails.aspx?regexp_id=167
		// I made a slight change on the pattern so that it can also handle gmail-style emails
		// like 'jim.barr+import@gmail.com'. The pattern is not used. [DY]
		// $email_pattern = '/^([\w_\-\.\+]+)@([\w_\-\.]+)\.([a-zA-Z]{2,5})$/i';
		
		$data_validation = new Data_Validation(NULL, NULL, NULL, NULL, NULL);
		
		$email = trim($email);
		$email  = $data_validation->Normalize_Engine($email, array('type' => 'email'));
		
		$result = $data_validation->Validate_Engine($email, array('type' => 'email'));
		
		if ($result['status'])
		{
			if (($max_length == 0) || (strlen($email) <= $max_length))
			{
				return TRUE;
			}
			else
			{
				return OLPWebService_Message::getMessage('EMAIL_EXCEED_MAX_LENGTH', $max_length);
			}
		}
		else
		{
			return OLPWebService_Message::getMessage('INVALID_EMAIL');
		}
	}
	
	/**
	 * Check if return type is valid or not.
	 * 
	 * @param string $return_type Return type. See OLPWebService::$return_types for details.
	 * @return boolean Return TRUE if given type is valid; otherwise, return FALSE.
	 */
	protected function hasValidReturnType($return_type)
	{
		if (in_array($return_type, $this->return_types))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Return a value in specified format back for SOAP requests. 
	 * 
	 * @param mixed $value Input value.
	 * @param int $return_type Return type. See OLPWebService::$return_types for details.
	 * @return string Output value.
	 */
	protected function getReturnValue($value, $return_type = NULL)
	{
		static $delimiter = ',';
		
		if (!$this->hasValidReturnType($return_type))
		{
			return OLPWebService_Message::getMessage('INVALID_RETURN_TYPE');
		}
		
		switch ($return_type)
		{
			case RETURN_CSV_STRING: // in CVS format
				if (is_array($value))
				{
					$str = implode('', $value);
					if (strpos($str, $delimiter) === FALSE)
					{
						return implode($delimiter, $value);
					}
					else
					{
						return OLPWebService_Message::getMessage('INVALID_DELIMITER_WHEN_RETURNNING_CSV');
					}
				}
				else
				{
					return OLPWebService_Message::getMessage('NOT_AN_ARRAY_WHEN_RETURNNING_CSV');
				}
				break;
			case RETURN_PHP_SERIALIZE_STRING: // in PHP serialized format
				return serialize($value);
				break;
			case RETURN_JSON_STRING: // in Json format
			default:
				return json_encode($value);
				break;
		}
	}
	
	/**
	 * Get database connection information.
	 *
	 * @param string $type type of server
	 * @param string $mode local/rc/live
	 * @return MySQL_Wrapper
	 */
	protected function getConnection($type = 'blackbox', $mode = BFW_MODE)
	{
		$this->setConnection($type, $mode);
		
		return $this->sql;
	}
	
	/**
	 * Set database connection information used by this web service.
	 * 
	 * @param string $type type of server
	 * @param string $mode local/rc/live
	 * @throws MySQL_Exception
	 */
	protected function setConnection($type = 'blackbox', $mode = BFW_MODE)
	{
		try
		{
			$this->sql = Setup_DB::Get_Instance($type, $mode); // BFW_MODE is defined in ./config.php
		}
		catch (MySQL_Exception $e)
		{
			throw $e;
		}
		
		$this->database = $this->sql->db_info['db'];
		
		// TODO: $this->sql->db_type is not properly set in method Setup_DB::Get_Instance().
		switch (strtolower($this->sql->db_type))
		{
			case 'mysql': // MySQL_4
				$this->sql->Select($this->database, TRUE);
				break;
			case 'mysqli': // MySQLi_1
				$this->sql->Change_Database($this->database);
				break;
			default:
				throw new Exception(OLPWebService_Message::getMessage('INTERNAL_ERROR_DB_WRAPPER'));
				break;
		}
	}

}

/**
 * An empty web service implementation used to handle incorrect SOAP requests.
 * For safe purpose, this class should be defined as a seperate class which doesn't contain any
 * methods except the magic one: __call().
 * 
 * @author Demin Yin <Demin.Yin@SellingSource.com>
 */
class OLPWebService_EmptyService
{
	
	/**
	 * Magic method used for handling invalid SOAP request and other errors.
	 * 
	 * @param string $name Function name.
	 * @param array $arguments Arguments.
	 * @return string A message indicated by OLPWebService_Message::$error_message_name.
	 */
	public function __call(string $name, $arguments)
	{
		return OLPWebService_Message::getMessage();
	}

}

/**
 * Handle all kinds of messages used in OLPWebService.
 * 
 * @author Demin Yin <Demin.Yin@SellingSource.com>
 */
class OLPWebService_Message
{
	
	/**
	 * Types of return messages for API calls.
	 * 
	 * A message returned could be in one of the following types:
	 *     * ERROR: Error happened before making the real API call. Generally it
	 *              means that the request doesn't have permission to access the
	 *              API (e.g., invalid license key/invalid promo id, etc), or 
	 * 				the request contains invalid data (e.g., invalid email address,
	 * 				etc).
	 *     * FAIL: Failed on the API call.
	 *     * PASS: Pass the API call.
	 * 
	 * This property is not actually used, but should be consider as part of the design.
	 * 
	 * @var array
	 */
	private static $message_types = array(
		'ERROR',
	
		'FAIL',
		'PASS',
	);
	
	/**
	 * Return messages for API calls.
	 * 
	 * Each entry in this message array is an array of 2 elements:
	 *     * A message type which is defined in self::$message_types;
	 *     * A message body which contain detailed message that would be returned back for API calls.
	 * 
	 * @var array
	 */
	private static $messages = array(
		// check email
		'INVALID_EMAIL' => array(
			'type'    => 'ERROR',
			'content' => 'No email or invalid email.',
		),
		'EMAIL_EXCEED_MAX_LENGTH' => array(
			'type'    => 'ERROR',
			'content' => 'Length of email address can not be greater than %d.',
		),
		
		// check license key, promo id
		'INVALID_LICENSE_KEY' => array(
			'type'    => 'ERROR',
			'content' => 'License key not found.',
		),		
		'INVALID_FORMAT_OF_PROMO_ID' => array(
			'type'    => 'ERROR',
			'content' => 'Invalid format of promo id.',
		),
		'INVALID_PROMO_ID' => array(
			'type'    => 'ERROR',
			'content' => 'Invalid promo id.',
		),
	
		// check return data
		'INVALID_RETURN_TYPE' => array(
			'type'    => 'ERROR',
			'content' => 'Invalid return type.',
		),
		'NOT_AN_ARRAY_WHEN_RETURNNING_CSV' => array(
			'type'    => 'ERROR',
			'content' => 'Given data is not an array When returning data in CVS format.',
		),
		'INVALID_DELIMITER_WHEN_RETURNNING_CSV' => array(
			'type'    => 'ERROR',
			'content' => 'Return data contain comma(s), and can\'t be converted into CSV format properly.',
		),
		
		// check message key
		'INVALID_MESSAGE_NAME' => array(
			'type'    => 'ERROR',
			'content' => 'Specified message doesn\'t exist.',
		),
		'MESSAGE_NAME_NOT_DEFINED' => array(
			'type'    => 'ERROR',
			'content' => 'Message name is not defined.',
		),
		
		// invalid web service calls
		'INVALID_WEB_SERVICE_NAME' => array(
			'type'    => 'ERROR',
			'content' => 'Service name not specified or specified service doesn\'t exist.',
		),
		'NON_IMPLEMENTED_SERVICE' => array(
			'type'    => 'ERROR',
			'content' => 'Non-implemented service).',
		),
		'WSDL_FILE_NOT_EXIST' => array(
			'type'    => 'ERROR',
			'content' => 'WSDL file doesn\'t exist.',
		),
		
		// internal errors
		'INTERNAL_ERROR_DB_WRAPPER' => array(
			'type'    => 'ERROR',
			'content' => 'Server side error (non-implemented database wrapper).',
		),
	);
	
	/**
	 * Specified error message name. Will be used when no arguments are provided when
	 * calling method self::getMessage().
	 * 
	 * @var string
	 */
	private static $error_message_name = '';
	
	/**
	 * Get the message which will be sent back for the SOAP request.
	 * 
	 * How to call this method?
	 * 
	 * Suppose 
	 * 
	 * 		OLPWebService_Message::$messages = array(
	 * 			'INVALID_EMAIL' => array(
	 * 				'type' => 'ERROR',
	 * 				'content' => 'No email or invalid email.',
	 *	 		),
	 * 			'EMAIL_EXCEED_MAX_LENGTH' => array(
	 * 				'type' => 'ERROR',
	 * 				'content' => 'Length of email address can not be greater than %d.',
	 * 			), 
	 * 		);
	 * 
	 * 1. OLPWebService_Message::getMessage('INVALID');
	 * 		  return value: "ERROR: No email or invalid email.".
	 * 
	 * 2. OLPWebService_Message::getMessage('EMAIL_EXCEED_MAX_LENGTH', 100);
	 * 		  return value: "ERROR: Length of email address can not be greater than 100.".
	 * 
	 * 3.  OLPWebService_Message::setErrorMessageName('INVALID');OLPWebService_Message::getMessage();
	 * 		  return value: "ERROR: No email or invalid email.".
	 * 
	 * @param string $message_name Message name.
	 * @param mixed ...... Arguments used by the message specified.
	 * @return string Message specified by message name.
	 */
	public static function getMessage()
	{
		if (func_num_args() == 0)
		{
			if (self::getErrorMessageName())
			{
				$message_name = self::getErrorMessageName();
			}
			else
			{
				$message_name = 'MESSAGE_NAME_NOT_DEFINED';
			}
		}
		else
		{
			$args = func_get_args();
			$message_name = $args[0];
		
		}
		
		if (!in_array($message_name, array_keys(self::$messages)))
		{
			$message_name = 'INVALID_MESSAGE_NAME';
		}
		
		$return_message = implode(': ', self::$messages[$message_name]);
		if (func_num_args() > 1) // with customized paramters (should use function sprintf())
		{
			$args[0] = $return_message;
			$return_message = call_user_func_array('sprintf', $args);
		}
		
		return $return_message;
	}
	
	/**
	 * Add a message.
	 * 
	 * Message format:
	 *     array(
	 *          'type'    => 'SOMETHING', // one of self::$message_types
	 *          'content' => 'SOMETHING', // message content
	 *     );
	 * 
	 * @param string $message_name Message name.
	 * @param array $message Message details.
	 */
	public static function addMessage($message_name, $message)
	{
		self::$messages[$message_name] = $message;
	}
	
	/**
	 * Get value for variable self::$error_message_name.
	 * 
	 * @return string Error message name.
	 */
	public static function getErrorMessageName()
	{
		return self::$error_message_name;
	}
	
	/**
	 * Set variable self::$error_message_name. 
	 * 
	 * @param string $error_message_name Message name.
	 */
	public static function setErrorMessageName($error_message_name)
	{
		self::$error_message_name = $error_message_name;
	}

}

/**********************************************************
 * OK, main procedure starts here.
 **********************************************************/

// If this script is included by other file (e.g., included by a ascipt to 
// generate WSDL file), then don't run the SOAP handler.
if (!isset($_REQUEST['web_service_included']) || empty($_REQUEST['web_service_included']))
{
	OLPWebService::runSoapHandler(); // entry point to the SOAP API
	exit(0);
}
