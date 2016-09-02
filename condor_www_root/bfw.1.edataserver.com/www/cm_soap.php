<?php
ini_set('include_path', '.:/virtualhosts:'.ini_get('include_path'));
require_once 'utf8_convert.php';
require_once 'maintenance_mode.php';
require_once('../include/code/crypt.singleton.class.php');
require_once('../include/code/crypt_config.php');

	/**
		@version 2.0
		@author Andrew Minerd

		@desc SOAP interface to OLP 6.0/BlackBox 2.0

		@todo TO DO:

		- The site type object in use should be verified for completeness:
			I essentially copied and pasted it

		- I think that the SOAP_Log class should become a part of the
		  OLP_Soap class... Probably as an extended version of OLP_Soap

		- Come up with a better method of making the WSDL file dynamic

		- We return all the data, all the time. Return only what they need:
			i.e. only fields with errors, or nothing (on some pages)

		--NOTES-----------------------------------------------------------

		> Even though we allow them to specify a site type, they are,
			in fact, restricted to the SOAP site type ('soap').

		> If a Unique ID is not specified, one is created outside of OLP:
			this is for logging purposes

		> Speaking of logging, at this point, the first SOAP request is
			logged _without_ a unique ID, since it comes without one

	*/
	//file_put_contents('/tmp/soap', 'rsk', FILE_APPEND);
	define('WSDL_FILE', realpath('olp.wsdl'));
	define('DEBUG', 0);

    //Reset MySQL Timer if on
    if(MYSQL4_LOG)
    {
        $_SESSION["mysql4_timer"] = (float)0;
        $_SESSION["mysql4_query_count"] = 0;
    }
    if(STATPRO_LOG)
    {
        $_SESSION["statpro_timer"] = (float)0;
    }

	// array of license keys that have
	// expired, and when they did
	$expired = array
	(

		// cashadvanceexert.com
		// 	turned back on, per Brian Rauch's request, 1/18/2006
	//	'ec68e0434d38efdda1ef04253e856e0d'=>strtotime('7/27/2005'), // LIVE
	//	'f6b9306ce7d49117e4d5e30fb25591a6'=>strtotime('7/27/2005'), // RC

		// payday911.com
		'324ec229a7208817065775dba228283c'=>strtotime('7/29/2005'), // LIVE
		'f3c319d06e517d1b403ca3125b06c6cc'=>strtotime('7/29/2005') // RC

	);

	// handles script errors
	function Error_Handler($error_type, $error_string, $error_file, $error_line)
	{

		if ($error_type & E_ERROR)
		{
			OLP_Soap::Fault_Unknown("{$error_file} ({$error_line}): $error_string");
		}

	}

	// make sure we have SOAP loaded
	if (!(extension_loaded('soap') || @dl('soap')))
	{
		Debug_Email('FATAL ERROR', 'The SOAP library is not loaded');
		die('The SOAP interface is temporarily unavailable. Please send your request again.');
	}

	ini_set("soap.wsdl_cache_enabled", "0");

	// we want to use our own error
	// handler so we can be more graceful
	use_soap_error_handler(FALSE);
	set_error_handler('Error_Handler');

	require_once 'config.php';
	$mode = BFW_MODE;

    //Block out people who sending real leads to RC
    $blocked = array('216.52.165.201');
    if(BFW_MODE == "RC" && in_array($_SERVER['REMOTE_ADDR'],$blocked, TRUE))
    {
    	echo "Please contact The Selling Source.";
    	exit(1);
    }

	//file_put_contents('/tmp/soap', 'cm soap', FILE_APPEND);
	// depending on which mode we're in,
	// determine which server we should hit
	switch (BFW_MODE)
	{

		case 'LOCAL':
			$url = 'bfw.1.edataserver.com.'.BFW_LOCAL_NAME.'.tss:8080';
		    break;

		case 'RC':
			if(BFW_MODE_NW)
			{
				$url = 'nw.bfw.1.edataserver.com';
			}
			else
			{
				$url = 'rc.bfw.1.edataserver.com';
			}
			ini_set('include_path', '../pear:/virtualhosts/rc_lib5:/virtualhosts/rc_lib:'.ini_get('include_path'));
			break;

		case 'LIVE':
            $expired['lic.olp.bb.sample'] = 0;
            $expired['lic.olp.bb.sample.dev'] = 0;
			$url = 'bfw.1.edataserver.com';
			break;

	}
	$url = "http://{$url}/cm_soap.php";

	// have to have includes here because of
	// include path settings above
	include_once('bfw.1.php');
	include_once('error_message_resource.php');
	include_once('mysql.4.php');
   	require_once '../include/code/OLP_Applog.php';

	// set up the SOAP server: the SOAP server will
	// call the methods specified in the WSDL file
	// as functions on our class: in this case,
	// OLP_Soap->User_Data()
	$server = new SoapServer(WSDL_FILE);
	$server->setClass('OLP_Soap', $mode);

	if ($_SERVER['QUERY_STRING'] != 'wsdl')
	{

		// make sure we have HTTP_RAW_POST_DATA: if we don't,
		// the SOAP server will throw an ugly fault, and we'd
		// rather throw that ourselves and be nicer
		if (isset($HTTP_RAW_POST_DATA) && (!empty($HTTP_RAW_POST_DATA)))
		{

			ob_start();

			// create our log object
			//Check if site is in maintenance mode
	        $maintenance_mode = new Maintenance_Mode();
	        if($maintenance_mode->Is_Online())
	        {
	            $log = new Soap_Log($mode);
	        }

			// process the call
			$server->handle();
			$sent = ob_get_flush();

			// log our soap response
			//$log->Log('SOAP_RESPONSE', $sent);

		}
		else
		{

			if ($_SERVER['REQUEST_METHOD']=='GET')
			{

				// get our WSDL file
				$temp = Parse_WSDL(WSDL_FILE, $url);

				echo("<html><head><title>Selling Source SOAP Interface</title></head><body>");
				echo("<b style=\"font-family: arial; font-size: 12pt;\">Selling Source SOAP Interface Version 2.0</b><br/>");
				echo("<p style=\"font-family: arial; font-size: 8pt; width: 500px; color: #808080;\">You've
								reached	the Selling Source SOAP interface. This page is not intended to be viewed with
								a web browser. If you are receiving this message while attempting to post a valid
								SOAP request, please contact the Selling Source.</p>");
				echo("<b style=\"font-family: arial; font-size: 12pt;\">Service Definition</b><br/>");
				echo("<pre style=\"font-size: 8pt;\">".htmlentities($temp)."</pre>");
				echo("</body></html>$mode");

			}
			else
			{
				// can't throw a fault :-(
				die('The Selling Source SOAP interface is temporarily unavailable');
			}

		}

	}
	else
	{

		// get our WSDL file
		$temp = Parse_WSDL(WSDL_FILE, $url);

		header('Content-Type: text/xml');
		echo($temp);

	}

    //Write MySQL Timer if on
    if(MYSQL4_LOG)
    {
        $page = (isset($_SESSION['data']['page'])) ? $_SESSION['data']['page'] : "unknown";

        session_write_close();

        $context = $_SESSION["mysql4_query_count"] . " queries";

        $applogM = new OLP_Applog("mysql4", "1000000000", 20, $context, true);
        $applogM->Write("Elapsed time for [Total Time:" . $page . "]  is " .
                       $_SESSION["mysql4_timer"] . " seconds.");
    }

    if(STATPRO_LOG)
    {
        $page = (isset($_SESSION['data']['page'])) ? $_SESSION['data']['page'] : "unknown";

        $applogS = new OLP_Applog("statpro", "1000000000", 20, "all", true);
        $applogS->Write("Elapsed time for [Total Time:" . $page . "]  is " .
                        $_SESSION["statpro_timer"] . " seconds.");
    }

	exit;

	/**

		@desc For now, a simple method of making the WSDL
			file automatically compensate for which mode we're
			in.

	*/
	function Parse_WSDL($file, $server)
	{

		$temp = @file_get_contents($file);

		if ($temp!==FALSE)
		{
			$temp = preg_replace('/\s?location=\"[^\"]*\"/', " location=\"$server\"", $temp);
		}

		return($temp);

	}

	function Debug_Email($type = 'ERROR', $message = NULL, $trace = NULL, $vars = NULL)
	{

        if(BFW_MODE == "LOCAL" || BFW_MODE == "RC")
        {
    		if (is_null($vars)) $vars = get_defined_vars();

    		if (is_array($message)) $message = print_r($message, TRUE);
    		if (is_array($trace)) $trace = print_r($trace, TRUE);
    		if (is_array($vars)) $vars = print_r($vars, TRUE);
    		$request = print_r($_SERVER, TRUE);

    		if (empty($message)) $message = "No description of this error is available.";
    		if (empty($trace)) $trace = "No trace is available.";

    		$email = array();
    		$email[] = "DESCRIPTION";
    		$email[] = str_repeat('-', 72);
    		$email[] = $message;
    		$email[] = '';
    		$email[] = "HTTP REQUEST";
    		$email[] = str_repeat('-', 72);
    		$email[] = $request;
    		$email[] = '';
    		$email[] = "BACK TRACE";
    		$email[] = str_repeat('-', 72);
    		$email[] = $trace;
    		$email[] = '';
    		$email[] = "DEFINED VARIABLES";
    		$email[] = str_repeat('-', 72);
    		$email[] = $vars;

    		$email = implode("\n", $email);
    		mail('august.malson@sellingsource.com', 'SOAP '.$type, $email);
        }
		return;

	}

	class OLP_Soap
	{

		private $request;
		private $response;
		private $mode;
		private $log;
		private $reason; // Mantis #8361 [DY]
		

		public function __construct($mode = 'LOCAL')
		{

			// this sucks, but because of the way
			// the constructor is called by the
			// SOAP server, we can't pass this in
			// by reference... grr!
			$this->mode = $mode;
			
			$maintenance_mode = new Maintenance_Mode();
	        if($maintenance_mode->Is_Online())
            {
            	global $log;
				$this->log = &$log;
            }
		}

		public static function Fault_Unavailable($extra = NULL)
		{
			// just say we're unavailable
			throw new SoapFault('Service Unavailable', 'The Selling Source SOAP service is temporarily unavailable. '.$extra);
		}

		public static function Fault_Unknown($extra = NULL)
		{

			// send a debug email
			debug_email('ERROR', $extra);

			throw new SoapFault('Unknown', 'An unknown error occured while processing your request.');

		}

		/**

			@desc This is the actual function called by the
				SOAP server. It is defined in the WSDL document
				as an operation for this end-point.

			@param $xml_data string <tss_loan_request>...</tss_loan_request>

			@return string <tss_loan_response>...</tss_loan_response>

		*/
		public function User_Data($xml_data)
		{
			//file_put_contents('/tmp/soap', 'user data', FILE_APPEND);
			global $expired;

			$response = NULL;

			// don't let random stuff get spit out
			ob_start();

			try
			{

				if ($xml_data)
				{

					$request = new OLP_Request($xml_data);

					if ($request instanceof OLP_Request)
					{

                        //Check if site is in maintenance mode
                        $maintenance_mode = new Maintenance_Mode();
	        			if(!$maintenance_mode->Is_Online())
                        {
                            $response = OLP_Response::Declined($request);
                            $response->Reason('Site down for maintenance');
                            return $response->To_XML();
                        }

						/*if ($this->log)
						{
							// log this!
							$this->log->Log('REQUEST', $request);
						}*/

						$license_key = $request->License_Key();

						// check to see if our license key has expired
						if ((!array_key_exists($license_key, $expired)) || (time() < $expired[$license_key]))
						{
							
							//Mantis #10740 -  WebAlerts functions used to block soap promo/sites from DOS attacks
							if(WebAlerts::isBlocked($request->data()))
							{
								$response = OLP_Response::Declined($request);
                            					$response->Reason('DOS Alert - Unauthorized Submission');


								//--- Commenting this out until Agean actually uses this. ---

								//Resell this lead to PW because it failed Agean.
								/*if($request->Site_Type() == 'soap.agean' && $response->Page() == 'app_declined')
								{
									/*
									 * Right now this uses cashloansofamerica's license keys.  We'll probably
									 * need to use something else, maybe even make up a fake site or something.
									 /
									switch(strtoupper($this->mode))
									{
										default:
										case 'LOCAL':	$lk = 'ccfbc42153db16b9eb1d1ed1073b5932'; break;
										case 'RC':		$lk = 'fd24d635c58f47139455e6d541f81622'; break;
										case 'LIVE':	$lk = '982396b45ca962de72652cc3926910d7'; break;
									}
									
									//We need to set up a new site type and license key,
									//as well as use a specific promo for these resells.
									$request->Site_Type('soap_oc');
									$request->License_Key($lk);
									$request->Promo_ID('99999');
									
									//We also make sure to blank out the unique_id so we get a new app
									$request->Unique_ID('');
									
 									//Finally, set the received data to the new XML output
									//so that it shows up properly in soap_data_log
									$request->Received($request->To_XML());
									
									$eds_response = $this->Run_OLP($request);
									if($eds_response !== FALSE)
									{
										$response = new OLP_Response($eds_response, $request->Unique_ID());
									}
								}*/
							}
							else 
							{
								// run OLP
								$eds_response = $this->Run_OLP($request);
								
								if ($eds_response!==FALSE)
								{
									// build our response object
									$response = new OLP_Response($eds_response, $request->Unique_ID());
								}
							}
						}
						else
						{
							// our license key has expired, so deny them
							$response = OLP_Response::Declined($request);
							$response->Reason('Invalid or expired license key.');
						}

					}

				}

			}
			catch (Exception $e)
			{

				// send a debug email
				debug_email('EXCEPTION', $e->getMessage(), $e->getTrace(), get_defined_vars());
				$response = NULL;

			}

			if (is_null($response))
			{

				if (is_object($request))
				{

					// if we have a request object,
					// gracefully decline this lead
					$response = OLP_Response::Declined($request);
					if (!$this->reason)
						$response->Reason('An unknown error occured while processing your loan.');
					else
						$response->Reason($this->reason);
				}
				else
				{
					// otherwise, just say we're unavailable
					self::Fault_Unknown("INVALID REQUEST: \n".print_r($xml_data, TRUE));
				}

			}

			// at this point, we should always have a
			// response - if we've thrown a fault, we
			// won't ever get here
			if ($response instanceof OLP_Response)
			{

				if ($this->log)
				{
					// log this!
					$this->log->Log('RESPONSE', $response);
				}

				// convert the response to XML
				$response = $response->To_XML();

			}

			// clean up any random junk
			ob_end_clean();

			return($response);

		}

		/**

			@desc Instantiate BFW and run OLP.

			@param $request OLP_Request Request from the client

			@return object EDS_RESPONSE from OLP

		*/
		private function Run_OLP($request)
		{
			
			//file_put_contents('/tmp/soap', 'run olp', FILE_APPEND);
			$return = FALSE;

			// only allow them to pages other than
			// app_allinone if they have a session ID
			if (($request->Page() == 'app_allinone') || ($request->Unique_ID()))
			{
				// build a session ID if we don't
				// already have one
				if (!$request->Unique_ID())
				{
					$session_id = md5(microtime().rand(0, 100));
					$request->Signature()->Value('unique_id', $session_id);
				}

				// rsk log here since we have unique_id at this point
				if ($this->log)
				{
					// log this!
					$this->log->Log('REQUEST', $request);
				}
				// rsk

				// convert to an array
				$data = $request->Data();

				// debugging options...
				if (strtoupper($this->mode) != 'LIVE')
				{
					$data['cashline'] = '0';
					$data['used_info'] = '0';
					$data['stats'] = '0';
					if(!isset($data['datax_idv'])) $data['datax_idv'] = '0';
					if(!isset($data['datax_perf'])) $data['datax_perf'] = '0';
				}

				$mode = $this->mode;

				switch ($request->Site_Type())
				{
					case 'soap_no_esig':
						$site_type = 'soap_no_esig';
						break;

					// online confirmation
					case 'soap_oc':
						$site_type = 'soap_oc';
						break;

					//Ecash React
					case 'blackbox.valucash.one.page':
						$site_type = 'blackbox.valucash.one.page';
						break;
						
					case 'soap.agean.react':
					case 'soap.agean.title.react':
					case 'soap.agean':
					case 'soap.agean.title':
						$site_type = $request->Site_Type();
						break;

					default:
						$site_type = 'soap';
						break;
				}

				// setup the framework
				$bfw = new Base_Frame_Work($request->License_Key(),
	                                           $data,
	                                           $mode,
	                                           $site_type,
	                                           $request->Unique_ID());
	                                           
	                                           
				//Mantis #8617 - inTimeCap() returns true if time falls within the span. [MJ]
				if (
					!$bfw->In_Blocked_Sub_Codes() 
					&& $bfw->inTimeCap()
				)
				{ // Mantis #8361: Disable Accepting Leads based on Sub Promo [DY]
					// run and return response from page handler
					$return = $bfw->module->Page_Handler($data);
					
				} 
				else 
				{
					$this->reason = 'This lead has been automatically rejected.';
				}

				if (!is_object($return)) $return = FALSE;

				// for some reason, the session barfs if
				// we keep it alive past here
				//session_commit();
			}
			
			return($return);

		}
		

	}
	
	/**
	 * Static class using webalerts to automatically reject leads.
	 * (Web alerts is a system setup to prevent DOS attacks)
	 *
	 */
	class WebAlerts
	{
		
		public function isBlocked($data)
		{
			// Just in case something breaks, lets default everything to NOT BLOCKED
			// so we do not cut off any revenue streams unintentionally
			$is_blocked = false;
			$is_blocked = $is_blocked || self::checkPromoID($data['promo_id']);
			$is_blocked = $is_blocked || self::checkSiteName($data['client_url_root']);
			
			return $is_blocked;
		}
		
		private function checkPromoID($promo_id)
		{
			
			// Just in case something breaks, lets default everything to NOT BLOCKED
			// so we do not cut off any revenue streams unintentionally
			$is_blocked = FALSE;
			$sql = Setup_DB::Get_Instance('blackbox', BFW_MODE);
			
			if(isset($promo_id))
			{				
				$query = "
					SELECT astat.status_name	
					FROM alerts a
					JOIN alert_status astat using (status_id)
					WHERE a.promo_id = $promo_id
					ORDER BY a.date_modified DESC
					LIMIT 1
				";
				
				try 
				{
					
					$results = $sql->Query('webalerts',$query);
					$result_row = $sql->Fetch_Array_Row($results);
					
				}
				catch (Exception $e)
				{
					
				}		
				
				
				if($result_row['status_name'] == 'BLOCKED')
				{
					$is_blocked = TRUE;	
				}
			}


				
			return $is_blocked;			
		}
		
		private function checkSiteName($site_name)
		{
			// Just in case something breaks, lets default everything to NOT BLOCKED
			// so we do not cut off any revenue streams unintentionally
			$is_blocked = FALSE;
			$sql = Setup_DB::Get_Instance('blackbox', BFW_MODE);
			
			
			preg_match("/[^\.\/]+\.[^\.\/]+$/",$site_name,$matches);
			$domain = mysql_escape_string($matches[0]);

			if(isset($domain))
			{	
				$query = "
					SELECT 
						astat.status_name
					FROM 
						webalerts.alerts a
					JOIN	webalerts.alert_status astat using (status_id)
					JOIN  	management.license_map lm ON a.page_id = lm.page_id
					WHERE 
						lm.site_name = '$domain'
						AND lm.mode = 'LIVE'
					ORDER BY a.date_modified DESC
					LIMIT 1
					";
			
				try 
				{
					$results = $sql->Query('webalerts',$query);
					$row = $sql->Fetch_Array_Row($results);
				}
				catch (Exception $e)
				{
					
				}
				if($row['status_name'] == 'BLOCKED')
					$is_blocked = TRUE;			
			
			}


			return $is_blocked;	
		}
	}

	class Soap_Log
	{

		protected $mode;
		protected $sql;
		protected $table;
		protected $compression;
		protected $crypt;
		private $start;

		public function __construct($mode, $table = 'soap_data_log', $compression = '')
		{

			// create database connection
			$this->mode = $mode;
			$this->table = $table;
			$this->compression = $compression;

			// connect
			$this->Connect($mode);
			$crypt_config = Crypt_Config::Get_Config($mode);
			$this->crypt = Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);
			// start our timer
			$this->start = microtime(TRUE);

		}

		public function __destruct()
		{

			unset($this->sql);

		}

		private function Connect($mode)
		{

			try
			{

				$db = Server::Get_Server($mode, 'BLACKBOX');
				$this->db = $db['db'];

				$this->sql = new MySQL_4($db['host'], $db['user'], $db['password']);
				$this->sql->Connect();

			}
			catch(Exception $e)
			{
				$this->sql = NULL;
			}

		}

		public function Log($type, $data)
		{

			$result = FALSE;
			$type = strtoupper($type);
			$unique_id = NULL;
			$email = NULL;

			switch ($type)
			{

				case 'REQUEST':

					if ($data instanceof OLP_Request)
					{
						$unique_id = $data->Unique_ID();
						$email = $data->Email();
						$site = $data->Collection()->Value('client_url_root');
						$data = $data->Received();
					}

					break;

				case 'RESPONSE':

					if ($data instanceof OLP_Response)
					{
						$unique_id = $data->Unique_ID();
						$email = $data->Email();
						$site = $data->Collection()->Value('client_url_root');
						$data = $data->To_XML();
					}

					break;

				case 'SOAP_REQUEST':
				case 'SOAP_RESPONSE':

					$unique_id = $this->Extract_Field($data, 'unique_id');
					$email = $this->Extract_Field($data, 'email_primary');
					$site = $this->Extract_Field($data, 'client_url_root');

					break;

				default:
					$type = FALSE;
					break;

			}

			$email = mysql_escape_string($email);
			$site = mysql_escape_string($site);
			
			//Escaping won't be necessary if we are encrypting the data
			//$data = mysql_escape_string($data);

			if ($this->sql && $type)
			{

				// our elapsed time since we were created
				$elapsed = (microtime(TRUE) - $this->start);

				try
				{
					// escape data

					$data_escaped = str_replace("'","&#039;",$data);
					
					$data_encrypted = $this->crypt->encrypt($data_escaped);
				
					$query = "INSERT INTO {$this->table} (date_created, unique_id, email, remote_site, data, elapsed, type,encrypted)
						VALUES (NOW(), '{$unique_id}', '{$email}', '{$site}', '{$data_encrypted}', {$elapsed}, '{$type}',1)";
					$this->sql->Query($this->db, $query);

					$result = ($this->sql->Affected_Row_Count() > 0);

				}
				catch (Exception $e)
				{
				}

			}

			return($result);

		}

		private function Extract_Field($xml, $name)
		{

			$value = '';

			if (preg_match('/(\s+)name=(\&quot;|\")'.$name.'(\&quot;|\")\&gt;([^&]*)\&lt;/i', $xml, $matches))
			{
				$value = trim($matches[4]);
			}

			return($value);

		}

		private function Compress(&$data, $compression)
		{

			switch (strtoupper($compression))
			{

				case 'GZ':
					$compressed = @gzcompress($data);
					break;

				case 'BZ':
					$compressed = @bzcompress($data);
					break;

			}

			return($compressed);

		}

	}

	/**

		@desc Encapsulates the tss_loan_request element of
		an OLP SOAP request.

	*/
	class OLP_Request
	{

		private $received;
		private $signature;
		private $collection;

		public function __construct($xml = NULL)
		{

			if (is_string($xml))
			{
				$this->From_XML($xml);
			}

		}

		public function &Signature($signature = NULL)
		{

			if ($signature instanceof OLP_Signature)
			{
				$this->signature = $signature;
			}

			return($this->signature);

		}

		public function &Collection($collection = NULL)
		{

			if ($collection instanceof OLP_Collection)
			{
				$this->collection = $collection;
			}

			return($this->collection);

		}

		public function License_Key($value = NULL)
		{

			$license_key = FALSE;

			if ($this->signature)
			{
				$license_key = $this->signature->Value('license_key', $value);
			}

			return($license_key);

		}

		public function Site_Type($value = NULL)
		{

			$site_type = FALSE;

			if ($this->signature)
			{
				$site_type = $this->signature->Value('site_type', $value);
			}

			return($site_type);

		}

		public function Page($value = NULL)
		{
			$page = FALSE;

			if ($this->signature)
			{
				$page = $this->signature->Value('page', $value);
			}

			return($page);

		}

		public function Unique_ID($value = NULL)
		{
			$unique_id = FALSE;

			if ($this->signature)
			{
				$unique_id = $this->signature->Value('unique_id', $value);
			}

			return($unique_id);

		}
		
		public function Promo_ID($value = NULL)
		{
			$promo_id = FALSE;
			
			if($this->signature)
			{
				$promo_id = $this->signature->Value('promo_id', $value);
			}
			
			return $promo_id;
		}
		
		public function Promo_Sub_Code($value = NULL)
		{
			$promo_sub_code = FALSE;
			
			if($this->signature)
			{
				$promo_sub_code = $this->signature->Value('promo_sub_code', $value);
			}
			
			return $promo_sub_code;
		}

		public function Email()
		{

			$email = FALSE;

			if ($this->collection)
			{
				$email = $this->collection->Value('email_primary');
			}

			return($email);

		}

		public function Received($value = NULL)
		{
			if(!is_null($value))
			{
				$this->received = $value;
			}

			return $this->received;
		}

		public function Data()
		{

			$user_data = FALSE;

			if ($this->signature && $this->collection)
			{

				$sig_data = $this->signature->To_Array();
				$user_data = $this->collection->To_Array();

				// translate the "legal" request to the esig page for OLP
				$user_data = array_merge($user_data, $sig_data);
				switch (strtolower($user_data['page']))
				{
					case 'legal': $user_data['page'] = 'esig'; break;
					case 'lead_resell': $user_data['promo_override'] = TRUE; break;
				}
			}

			// hack to get the bank account type to the right value
			if (!array_key_exists('bank_account_type', $user_data) || empty($user_data['bank_account_type']))
			{
                //require_once '../include/code/OLP_Applog_Singleton.php';
                //$applog = OLP_Applog_Singleton::Get_Instance("soap", APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, "soap", APPLOG_ROTATE);
                //$applog->Write($user_data['client_url_root'] . " bank_account_type is blank");
                $user_data['bank_account_type'] = 'CHECKING';
			}

			return($user_data);

		}

		/**

			@desc Converts the request from XML
				to our objectimified structure.

		*/
		public function From_XML($xml)
		{

			// save this
			$this->received = $xml;

			// HACK: replace &s with &amp;s - the long term solution
			// is to have the vendors encode their data "twice":
			// Original: <data name="test">a & b</data>
			// Encode 1: <data name="test">a &amp; b</data>
			// Encode 2: &lt;data name=&quot;test&quot;&gt;a &amp;amp; b&lt;/data&gt;
			$xml = preg_replace('/(?<=>)([^>&]*[&][^<]*)(?=<)/e', 'htmlentities("$1")', $xml);

			// parse this XML
			$xml = '<?xml version="1.0"?>' . $xml;
			$simple_xml = @simplexml_load_string($xml);

			if (is_object($simple_xml))
			{

				// build the signature and collection
				// objects: very straightforward
				$this->signature = new OLP_Signature($simple_xml);
				$this->collection = new OLP_Collection($simple_xml, $this->Page());

			}
			else
			{
				throw new Exception('Malformed or invalid request.', 0);
			}

			unset($simple_xml);
			return;

		}

		public function To_XML()
		{

			$xml = FALSE;

			if ($this->signature && $this->collection)
			{

				$xml = '<tss_loan_request>';
				$xml .= $this->signature->To_XML();
				$xml .= $this->collection->To_XML();
				$xml .= '</tss_loan_request>';

			}

			return($xml);

		}

	}

	class OLP_Pages
	{

		/**

			@desc Returns a content object for the page specified in
				the EDS response. NOTE: This is based on the page name
				returned from OLP, not the SOAPimified page name:
				these may or may not be the same, and some cases
				depend upon the differences.

			@param $eds_response stdClass Response from OLP

			@return OLP_Content Content for the SOAP response

		*/
		public static function From_EDS_Response($eds_response, $response)
		{

			$page = NULL;
			$content = FALSE;
			if (isset($eds_response->page)) $page = strtolower($eds_response->page);
			if (isset($eds_response->data['site_type'])) $site_type = $eds_response->data['site_type'];

			//If we're an Agean site and Agean won, we need
			//to return a special response
			if(preg_match('/^soap\.agean(\.title)?$/i', $site_type)
				&& isset($eds_response->data['online_confirm_winner'])
				&& Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_AGEAN, $eds_response->data['online_confirm_winner'])
			)
			{
				$content = self::Agean_Response($eds_response);
			}
			else
			{
				switch($page)
				{
	
					case 'verify_address':
						$content = self::Page_Verify_Address($eds_response);
						break;
	
					case 'esig':
						$content = self::Page_ESignature($eds_response, $response);
						break;
	
					case 'preview_docs':
						$content = self::Page_Legal($eds_response);
						break;
	
					case 'bb_thanks':
						$content = self::Page_Thanks($eds_response);
						break;
	
					case 'app_done_paperless':
						$content = self::Page_Thanks_Enterprise($eds_response);
						break;
	
					case 'agent_react_confirm':
						$content = self::React_Response($eds_response);
						break;
	
					case 'app_allinone':
						$content = new OLP_Content();
						break;
	
					case 'cust_decline':
						// display the customer declined page - they declined the loan on the legal page
						$content = self::Cust_Declined();
						break;
	
					case 'app_declined':
					default:
						if(in_array($site_type, array('blackbox.valucash.one.page', 'soap.agean.react', 'soap.agean.title.react')))
						{
							$content = self::React_Response($eds_response);
						}
						else
						{
							$content = self::Page_Declined();
						}
						break;
	
				}
			}

			return($content);

		}

		/**

			@desc Translate the EDS page name into the correct
				page name for the SOAP guys. I don't actually change
				the page name on the eds object, because it's
				needed later to generate the correct content.

		*/
		public static function EDS_Page(&$eds_response)
		{

			$page = '';

			if (isset($eds_response->page))
			{
				$page = strtolower($eds_response->page);
			}

			switch ($page)
			{

				// we're finished!
				case 'bb_thanks':
				case 'app_done_paperless':
					$page = 'app_completed';
					break;

				// esig is legal to the SOAP guys
				case 'esig':
					$page = 'legal';
					break;

				case 'app_declined':
					$page = 'app_declined';
					break;

				case 'app_allinone':
					$page = 'app_allinone';
					break;

				case 'preview_docs':
					$page = 'preview_docs';
					break;

				case 'cust_decline':
				// since the cust_decline page doesn't exist, fake it so we can display
				// a message without breaking things
					$page = 'app_completed';
					break;

				//For Reacts
				case 'agent_react_confirm':
					$page = 'agent_react_confirm';
					break;

				// we shouldn't really ever get anything
				// else, but... just in case: also set
				// $eds_response->page so the right response
				// gets generated
				default:
					$page = 'app_declined';
					$eds_response->page = 'app_declined';
					break;

			}

			return($page);

		}

		private static function Page_Thanks($eds_response)
		{

			$content = new OLP_Content();
			$text = '';

			if (isset($eds_response->data['thanks_content']))
			{
				$text = $eds_response->data['thanks_content'];
			}

			if (isset($eds_response->data['online_confirm_redirect_url']))
			{
				$text = "<p>Congratulations, you have been approved with one of our lending partners.</p>
You will be redirected to their site in a moment. Please complete the next
steps, if requested.";
				$text .= $eds_response->data['redirect_time'];
			}
			$section = new OLP_Section($text);
			$content->Add_Section($section);

			return($content);

		}

		private static function Page_Thanks_Enterprise($eds_response)
		{

			$content = new OLP_Content();
			$name_first = '';

			// we shouldn't get to this page for soap oc sites but just in case
			if (isset($eds_response->data['online_confirm_redirect_url']))
			{
				$text = "Congratulations, you have been approved with one of our lending partners.<p>
You will be redirected to their site in a moment.";
				$text .= $eds_response->data['redirect_time'];
			}

			else

			{
				if (isset($eds_response->data['name_first']))
				{
					$name_first = $eds_response->data['name_first'];
				}

			$text = '<div align=left><h3 style="font-family: Arial, Helvetica, sans-serif">Welcome ' .  $name_first . ' Thank You for your Application!</h3>
<p style="font-family: Arial, Helvetica, sans-serif">Your information and application have been successfully submitted.</p></div>';
			$content->Add_Section(new OLP_Section($text));

			$text = '<div align=left><h3 style="font-family: Arial, Helvetica, sans-serif"><strong>THE FOLLOWING IS EXTREMELY IMPORTANT!</strong></h3>
<p style="font-family: Arial, Helvetica, sans-serif"><strong>You will receive an e-mail from us momentarily.</strong></p>
<ul>
  <li style="font-family: Arial, Helvetica, sans-serif"> PLEASE CHECK YOUR INBOX AND ANY SPAM FOLDERS FOR YOUR CONFIRMATION EMAIL!<br>
  </li>
  <li style="font-family: Arial, Helvetica, sans-serif"> You <strong>MUST</strong> follow the directions and confirm your details provided in your e-mail in order for us to process your loan and get your cash to you!<br>
  </li>
  <li style="font-family: Arial, Helvetica, sans-serif">  Due to increasing e-mail restrictions, this email-may accidentally be marked as spam and sent to your Bulk Mail (Yahoo!), Junk Mail (MSN Hotmail) or Spam (AOL) folder.</li>
</ul></div>';
		}

			$content->Add_Section(new OLP_Section($text));


			return($content);

		}

		private static function Agean_Response($eds_response)
		{
			$content = new OLP_Content();

			$content->Add_Section(
				new Agean_Section(
					$eds_response->data['application_id'],
					$eds_response->data['customer_service_link'],
					$eds_response->data['customer_id']
				)
			);

			return $content;
		}

		private static function React_Response($eds_response)
		{
			$content = new OLP_Content();

			$content->Add_Section(new React_Section($eds_response->data["application_id"],
													$eds_response->data["online_confirm_redirect_url"]));

			return($content);
		}

		public static function Page_Declined()
		{

			$content = new OLP_Content();
			$text = '<p>We\'re sorry but you do not qualify for a payday loan at this time.</p>';

			// create a new verbiage section
			$section = new OLP_Section($text);
			$content->Add_Section($section);

			return($content);

		}

		// customer did not agree on the esig page
		public static function Cust_Declined()
		{

			$content = new OLP_Content();

			$text = '<p>I\'m sorry we could not provide you with a loan at this time. We look forward to providing you service in the future. Please close your browser at this time..</p>';

			// create a new verbiage section
			$section = new OLP_Section($text);
			$content->Add_Section($section);

			return($content);

		}

		private static function Page_Verify_Address($eds_response)
		{

			$states = array('AK', 'AL', 'AR', 'AZ', 'CA', 'CO', 'CT', 'DC', 'DE', 'FL', 'GA', 'HI', 'IA', 'ID', 'IL',
				'IN', 'KS', 'KY', 'LA', 'MA', 'MD', 'ME', 'MI', 'MN', 'MO', 'MS', 'MT', 'NC', 'ND', 'NE', 'NH', 'NJ',
				'NM', 'NV', 'NY', 'OH', 'OK', 'OR', 'PA', 'PR', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VA', 'VI', 'VT',
				'WA', 'WI', 'WV', 'WY');

			$content = new OLP_Content();

			$text = "We checked US Postal Service records and didn't find your address. Please confirm your address.";
			$content->Add_Section(new OLP_Section($text));

			$question = new OLP_Question('text');
			$question->Options('home_street', array());
			$content->Add_Section(new OLP_Section('Address', $question));

			$question = new OLP_Question('text');
			$question->Options('home_unit', array());
			$content->Add_Section(new OLP_Section('Apartment', $question));

			$question = new OLP_Question('text');
			$question->Options('home_city', array());
			$content->Add_Section(new OLP_Section('City', $question));

			$question = new OLP_Question('combo');
			$question->Options('home_state', $states);
			$content->Add_Section(new OLP_Section('State', $question));

			$question = new OLP_Question('text');
			$question->Options('home_zip', array());
			$content->Add_Section(new OLP_Section('Zip Code', $question));

			return($content);

		}

		private static function Page_Legal($eds_response)
		{

			if (isset($eds_response->eds_page) && array_key_exists('content', $eds_response->eds_page))
			{

				// hijack legal document from EDS response
				$doc = $eds_response->eds_page['content'];

				// remove useless comments
				$doc = preg_replace('/<!--.*?-->/s', '', $doc);

				$content = new OLP_Content();

				$section = new OLP_Section($doc);
				$content->Add_Section($section);

			}
			else
			{
				throw new Exception('Legal document is missing.');
			}

			return($content);

		}

		private static function Page_ESignature($eds_response, $response)
		{

			// hijacked CSS
	   	$css = "
				<style>
					#wf-legal-section {	margin: 0 15px 0 15px;  padding-top: 10px; }
					.wf-legal-block {	background-color: #FFFFFF; margin 0; padding: 0; }
					.wf-legal-title {	background-color: #000000; color:#FFFFFF; font-size: 20px; font-weight: bold;
						text-align: left; width: auto; padding: 10px 0 10px 15px; }
					.wf-legal-table { padding: 0px; margin: auto; width: auto; }
					.wf-legal-table-cell, .wf-legal-table-cell h2, .wf-legal-table-cell h3  {
						padding: 3px; margin: 0; }
					.wf-legal-table-cell-terms { background-color: #FFFFFF; width: 50%; padding: 4px;
						margin: 0; text-align: center; border-top: 5px solid black; border-bottom: 5px solid black; }
					.wf-legal-table-cell-schedule { background-color: #FFFFFF; width: 50%; padding: 6px 8px  6px 10px;
						margin: 0; text-align: left; font-size: 10px; border-top: 5px solid black; border-bottom: 5px solid black; }
					.wf-legal-copy { font-size: 11px; text-align: left; padding: 0 15px 0 15px; }
					.wf-legal-copy li { font-size: 12px; list-style: none; margin: 3px; }
					.wf-legal-link { font-size: 10px; color: blue; }
				</style>
			";

			// get our esignature
			$esig = $eds_response->data['name_first'].' '.$eds_response->data['name_last'];
			$unique_id = $response->Signature()->Value('unique_id');

			$content = new OLP_Content();

			// top of the page
			$section = new OLP_Section('<h2 align="center">LOAN ACCEPTANCE & eSIGNATURE</h2>');
			$content->Add_Section($section);

			// some text
			$text = 'The terms of your loan are described in the
				<strong><a href="#" onClick="'.self::Open_Legal_Window('preview_docs', $unique_id, 'loan_note_and_disclosure').'">LOAN
				NOTE AND DISCLOSURE</a></strong> found below. Please review and accept the following documents.';

			// add this section
			$section = new OLP_Section($text);
			$content->Add_Section($section);

			// APPLICATION

			$text = 'I have read and accept the terms of the
				<strong><a href="#" onclick="'.self::Open_Legal_Window('preview_docs', $unique_id, 'application').'">application</a></strong>.';

			$question = new OLP_Question('radio');
			$question->Options('legal_approve_docs_1', array('TRUE', 'FALSE'));

			$section = new OLP_Section($text, $question);
			$content->Add_Section($section);

			// PRIVACY POLICY

			$text = 'I have read and accept the terms of the
				<strong><a href="#" onclick="'.self::Open_Legal_Window('preview_docs', $unique_id, 'privacy_policy').'">privacy
				policy</a></strong>.';

			$question = new OLP_Question('radio');
			$question->Options('legal_approve_docs_2', array('TRUE', 'FALSE'));

			$section = new OLP_Section($text, $question);
			$content->Add_Section($section);

			// AUTHORIZATION AGREEMENT

			$text = 'I have read and accept the terms of the
				<strong><a href="#" onclick="'.self::Open_Legal_Window('preview_docs', $unique_id, 'auth_agreement').'">authorization
				agreement</a></strong>.';

			$question = new OLP_Question('radio');
			$question->Options('legal_approve_docs_3', array('TRUE', 'FALSE'));

			$section = new OLP_Section($text, $question);
			$content->Add_Section($section);

			// LOAN NOTE and DISCLOSURE

			$text = 'I have read and accept the terms of the
				<strong><a href="#" onclick="'.self::Open_Legal_Window('preview_docs', $unique_id, 'loan_note_and_disclosure').'">loan
				note and disclosure</a></strong>.';

			$question = new OLP_Question('radio');
			$question->Options('legal_approve_docs_4', array('TRUE', 'FALSE'));

			$section = new OLP_Section($text, $question);
			$content->Add_Section($section);

			// LOAN NOTE and DISCLOSURE

			$text = 'To accept the terms of the
				<strong><a href="#" onclick="'.self::Open_Legal_Window('preview_docs', $unique_id, 'loan_note_and_disclosure').'">loan
				note and disclosure</a></strong>, provide your <strong>Electronic Signature</strong> by typing your
				full name below. This signature should appear as: '. $esig;

			$section = new OLP_Section($text);
			$content->Add_Section($section);

			// ESIGNATURE

			$text = '<b>eSIGNATURE</b> Enter your full name in the box.';

			$question = new OLP_Question('text');
			$question->Options('esignature', '');

			$section = new OLP_Section($text, $question);
			$content->Add_Section($section);

			// AGREE

			$text = 'I AGREE - Send Me My Cash';

			$question = new OLP_Question('radio');
			$question->Options('legal_agree', array('TRUE', 'FALSE'));

			$section = new OLP_Section($text, $question);
			$content->Add_Section($section);

			// LOAN DOCUMENT

			// hijack the legal document from the EDS response
			$doc = $css . $eds_response->data['esig_doc'];
			$doc = preg_replace('/<!--.*?-->/s', '', $doc);

			$section = new OLP_Section($doc);
			$content->Add_Section($section);

			return($content);

		}

		private static function Open_Legal_Window($page, $unique_id, $anchor = NULL)
		{

			if (!is_null($anchor)) $anchor = "#{$anchor}";

			$options = 'width=800,height=600,resizable=yes,scrollbars=yes,location=yes,toolbar=yes,menubar=yes';
			$out = "window.open('?page={$page}&unique_id={$unique_id}{$anchor}', 'tss_win', '{$options}'); return false;";

			return($out);

		}

	}

	/**

		@desc Encapsulate the <tss_loan_response> element

	*/
	class OLP_Response
	{

		private $received;
		private $signature;
		private $errors;
		private $content;
		private $collection;

		public function __construct(&$eds_response = NULL, $session_id = NULL)
		{

			if (is_object($eds_response))
			{
				$this->From_EDS_Response($eds_response, $session_id);
			}

		}

		/**

			@desc Build a response object for a declined loan,
				used for errors and such.

		*/
		public static function Declined(&$request)
		{

			$new = New OLP_Response();

			// steal the signature from our request,
			// but set the page to declined
			$new->signature = $request->Signature();
			$new->signature->Value('page', 'app_declined');

			// no errors, and steal the request collection
			$new->errors = new OLP_Errors();
			$new->collection = $request->Collection();

			// the declined page
			$new->content = OLP_Pages::Page_Declined();

			return($new);

		}

		public function &Signature($signature = NULL)
		{

			if ($signature instanceof OLP_Signature)
			{
				$this->signature = $signature;
			}

			return($this->signature);

		}

		public function &Errors($errors = NULL)
		{

			if ($errors instanceof OLP_Errors)
			{
				$this->errors = $errors;
			}

			return($this->errors);

		}

		public function &Collection($collection = NULL)
		{

			if ($collection instanceof OLP_Collection)
			{
				$this->collection = $collection;
			}

			return($this->collection);

		}

		public function Reason($reason = NULL)
		{

			if ($this->signature)
			{
				if (!is_null($reason)) $this->signature->Value('reason', $reason);
				$reason = $this->signature->Value('reason');
			}
			else
			{
				$reason = NULL;
			}

			return($reason);

		}

		public function Received()
		{
			return($this->received);
		}

		public function Page()
		{

			$page = FALSE;

			if ($this->signature)
			{
				$page = $this->signature->Value('page');
			}

			return($page);

		}

		public function Unique_ID()
		{

			$unique_id = FALSE;

			if ($this->signature)
			{
				$unique_id = $this->signature->Value('unique_id');
			}

			return($unique_id);

		}

		public function Email()
		{

			$email = FALSE;

			if ($this->collection)
			{
				$email = $this->collection->Value('email_primary');
			}

			return($email);

		}

		public function Cache()
		{

			$cache = FALSE;

			if ($this->signature && ($this->Page() != 'preview_docs'))
			{
				$cache = TRUE;
			}

			return($cache);

		}

		public function From_XML($xml)
		{

			// save this
			$this->received = $xml;

			// parse this XML
			$xml = '<?xml version="1.0"?>' . $xml;
			$simple_xml = @simplexml_load_string($xml);

			if (is_object($simple_xml))
			{

				// build the signature and collection objects:
				// very straightforward
				$this->signature = new OLP_Signature($simple_xml);
				$this->collection = new OLP_Collection($simple_xml, $this->Page());
				$this->errors = new OLP_Errors($simple_xml);
				$this->content = new OLP_Content($simple_xml);

			}
			else
			{
				throw new Exception('Malformed or invalid response.');
			}

			unset($simple_xml);
			return;

		}

		public function To_XML()
		{

			$xml = '<tss_loan_response>';
			if ($this->signature)	$xml .= $this->signature->To_XML();
			if ($this->errors) $xml .= $this->errors->To_XML();
			if ($this->content) $xml .= $this->content->To_XML();
			if ($this->collection) $xml .= $this->collection->To_XML();
			$xml .= '</tss_loan_response>';

			return($xml);

		}

		public function From_EDS_Response(&$eds_response, $session_id = NULL)
		{

			// build our individiual objects
			$this->signature = new OLP_Signature();
			$this->signature->From_EDS_Response($eds_response, $session_id);

			// build our errors
			$this->errors = new OLP_Errors();
			$this->errors->From_EDS_Response($eds_response);

			$this->collection = new OLP_Collection();
			$this->collection->From_EDS_Response($eds_response, $this->Page());

			// build our page: done last so we have access
			// to all other sections
			$this->content = OLP_Pages::From_EDS_Response($eds_response, $this);

			return;

		}

	}

	/**

		@desc Handles an "array" of simple elements.

			Most of the content of the XML passed in our SOAP
			calls is made up of a "base" element containing
			a bunch of data elements. For instance:

			<tss_loan_response>
				<errors><data name="income_monthly_net">Your income...
				<signature><data name="site_type">blackbox.one.page</...
				[...]
			</tss_loan_response>

			This class makes it easy to work with these
			elements.

	*/
	class XML_Data_Array
	{

		protected $base_element;
		protected $data_element;
		protected $name_attribute;
		protected $subdata_element;

		protected $allowed;
		protected $data;

		public function __construct($base_element, $data_element = 'data', $name_attribute = 'name', $data = NULL)
		{

			$this->base_element = $base_element;
			$this->data_element = $data_element;
			$this->subdata_element = "subdata";
			$this->name_attribute = $name_attribute;

			$this->data = array();

			if (is_string($data))
			{
				$this->From_XML($data);
			}
			elseif (is_array($data))
			{
				$this->From_Array($data);
			}
			elseif (is_object($data))
			{
				$this->From_Simple_XML($data);
			}

		}

		public function Value($name, $value = NULL)
		{

			if (!is_null($value))
			{
				if ($this->Valid($name, $value))
				{
					$this->data[$name] = $value;
				}
			}
			else
			{
				if (array_key_exists($name, $this->data))
				{
					$value = $this->data[$name];
				}
			}

			return($value);

		}

		public function From_XML($xml)
		{

			$simple = simplexml_load_string($xml);
			$this->From_Simple_XML($simple);

			return($this->data);

		}

		public function To_XML()
		{

			if (count($this->data))
			{

				// make things pretty: this will pu the data array
				// in the same order as the allowed array
				if (is_array($this->allowed))
				{
					$order = array_intersect(array_keys($this->allowed), array_keys($this->data));
					$this->data = array_merge($order, $this->data);
				}

				$xml = "<{$this->base_element}>";

				foreach ($this->data as $name=>$value)
				{
					if(is_array($value))
					{
						$xml .= "<{$this->data_element} {$this->name_attribute}=\"{$name}\">";
						foreach($value as $n => $v)
						{
							$xml .= "<{$this->subdata_element} {$this->name_attribute}=\"{$n}\">".UTF8_Convert::Encode($v,true)."</{$this->subdata_element}>";
						}
						$xml .= "</{$this->data_element}>";
					}
					elseif($value != '')
					{
						$xml .= "<{$this->data_element} {$this->name_attribute}=\"{$name}\">".UTF8_Convert::Encode($value,true)."</{$this->data_element}>";
					}
					else
					{
						$xml .= "<{$this->data_element} {$this->name_attribute}=\"{$name}\"/>";
					}
				}

				$xml .= "</$this->base_element>";

			}
			else
			{
				$xml = "<{$this->base_element}/>";
			}

			return($xml);

		}

		public function From_Simple_XML($simple)
		{
			// find all data_elements under the base_element
			$elements = $simple->xpath("//{$this->base_element}/{$this->data_element}");

			// reset
			$this->data = array();

			foreach ($elements as $element)
			{
				//PHP 5.1+
				if(version_compare(phpversion(), '5.1.0', '>='))
				{
					//convert to array
					$aelement = (array)$element;

					//Get Name Out
					$name = $aelement['@attributes']['name'];

					if(isset($aelement['subdata']))
					{
						$subdata_elements = $element->xpath("//{$this->data_element}/{$this->subdata_element}");
						$value = array();
						foreach($subdata_elements as $e)
						{
							$e = (array)$e;
							$n = $e['@attributes']['name'];

							$v = UTF8_Convert::Decode(trim($e[0]),true);
							$value[$n] = htmlentities($v);
						}
					}
					else
					{
						$value = htmlentities(UTF8_Convert::Decode(trim($aelement[0]),true));
					}
				}
				else
				{
					//PHP 5
					$attr = $element->attributes();
					$name = (string)$attr[$this->name_attribute];

					//convert to array
					$aelement = (array)$element;
					$aelement = reset($element);

					if(is_array($aelement))
					{
						$subdata_elements = $element->xpath("//{$this->data_element}/{$this->subdata_element}");

						$value = array();
						foreach($subdata_elements as $e)
						{
							$attr = $e->attributes();
							$n = (string)$attr[$this->name_attribute];

							$v = UTF8_Convert::Decode(trim((string)$e),true);
							$value[$n] = htmlentities($v);
						}
					}
					else
					{
						$value = htmlentities(UTF8_Convert::Decode(trim($aelement),true));
					}
				}
				// make sure it's either allowed, or we
				// are allowing anything
				if ($this->Valid($name, $value))
				{
					$this->data[$name] = $value;
				}

			}

			return($this->data);
		}

		public function From_Array($array)
		{

			// reset
			$this->data = array();

			if (is_array($array))
			{

				// if we're allowing anything,
				// just bring the whole array over
				if (!is_array($this->allowed))
				{
					$this->data = $array;
				}
				else
				{

					$keys = $this->Valid($array);

					foreach ($keys as $name)
					{
						$this->data[$name] = $array[$name];
					}

				}

			}

			return;

		}

		public function To_Array()
		{

			return($this->data);

		}

		public function Allowed()
		{

			return(self::$allowed);

		}

		protected function Valid($name, $value = NULL)
		{
			if (is_string($name))
			{
				$valid = ((!is_array($this->allowed)) || in_array($name, $this->allowed));
			}
			elseif (is_array($name))
			{

				$temp = array_keys($name);

				if (is_array($this->allowed))
				{
					$valid = array_intersect($this->allowed, $temp);
				}
				else
				{
					$valid = $temp;
				}

			}

			return($valid);

		}

	}

	/**

		@desc Encapsulate the <content> tag.

	*/
	class OLP_Content
	{

		protected $sections;

		public function __construct($data = NULL)
		{

			$this->sections = array();

			if (is_object($data))
			{
				$this->From_Simple_XML($data);
			}

		}

		public function Add_Section($section)
		{

			$this->sections[] = $section;
			return;

		}

		public function To_XML()
		{

			if (count($this->sections))
			{

				$xml = '<content>';

				foreach ($this->sections as &$section)
				{
					$xml .= $section->To_XML();
				}

				$xml .= '</content>';

			}
			else
			{
				$xml = '<content/>';
			}

			return($xml);

		}

		public function From_Simple_XML($simple)
		{

			// find all content/section elements with a verbiage
			// or question (child) element
			$sections = $simple->xpath('//content/section[verbiage|question]');

			foreach ($sections as $element)
			{

				// parse the section
				$section = new OLP_Section();
				$section->From_Simple_XML($element);

				// add it to our list
				$this->sections[] = $section;

			}

		}

	}

	/**

		@desc Encapsulate the <section> element. This
			lives under the <content> element, and may contain
			a <verbiage> or <question> element (or both).

	*/
	class OLP_Section
	{

		protected $verbiage;
		protected $question;

		public function __construct($verbiage = NULL, $question = NULL)
		{

			if (is_string($verbiage)) $this->verbiage = $verbiage;
			if ($question instanceof OLP_Question) $this->question = $question;

		}

		public function Verbiage($text = NULL)
		{

			if (is_string($text)) $this->verbiage = $text;
			return($this->verbiage);

		}

		public function Question($question = NULL)
		{

			if ($question instanceof OLP_Question) $this->question = $question;
			return($this->question);

		}

		public function To_XML()
		{

			$xml = "<section>";

			if ($this->verbiage)
			{

				$xml .= "<verbiage>";

				// if we don't have any <'s, assume it's
				// just plain text - otherwise, use a
				// CDATA tag
				if (strpos($this->verbiage, '<')===FALSE) $xml .= htmlentities($this->verbiage);
				else $xml .= "<![CDATA[{$this->verbiage}]]>";

				$xml .= "</verbiage>";

			}

			if ($this->question)
			{
				$xml .= $this->question->To_XML();
			}

			$xml .= "</section>";

			return($xml);

		}

		/**

			@todo Finish this!

		*/
		public function From_XML($xml)
		{



		}

		public function From_Simple_XML($simple)
		{

			if (isset($simple->verbiage))
			{
				$this->verbiage = (string)$simple->verbiage;
			}

			if (isset($simple->question))
			{

				$question = new OLP_Question();
				$question->From_Simple_XML($simple->question);

				$this->question = $question;

			}

		}

	}

	/**
	 * Agean Section
	 *
	 * This builds the react section
	 */
	class Agean_Section
	{
		protected $application_id;
		protected $site_url;
		protected $customer_id;
		protected $site_id;
		protected $hash;

		public function __construct($application_id, $site_url, $customer_id)
		{
			$this->application_id = $application_id;
			$this->site_url = $site_url;
			$this->customer_id = $customer_id;
			$this->site_id = (int)Enterprise_Data::getEnterpriseOption(Enterprise_Data::getProperty($site_url), 'site_id');
			
			$this->hash = md5($this->application_id . $this->site_id . 'L08N54M3');
		}

		public function To_XML()
		{
			$url = htmlentities($this->site_url);
			$app_id = base64_encode($this->application_id);
			$xml =<<<XML
<section>
	<applicationid>{$app_id}</applicationid>
	<customerid>{$this->customer_id}</customerid>
	<siteid>{$this->site_id}</siteid>
	<winnerurl><![CDATA[{$url}]]></winnerurl>
	<login>{$this->hash}</login>
</section>
XML;
			return $xml;
		}

		public function From_XML($xml) {}

		public function From_Simple_XML($simple) {}
	}

	/**
	 * React Section
	 *
	 * This builds the react section
	 */
	class React_Section
	{
		protected $app_id;
		protected $link;

		public function __construct($app_id, $link)
		{
			$this->app_id = $app_id;
			$this->link = $link;
		}

		public function To_XML()
		{
			$xml = "<section>";
			$xml.= "<application_id>" . $this->app_id . "</application_id>";
			$xml.= "<link><![CDATA[" . htmlentities($this->link) . "]]></link>";
			$xml.= "</section>";

			return($xml);
		}

		public function From_XML($xml) {}

		public function From_Simple_XML($simple) {}
	}

	/**

		@desc Encapsulate the <question> element:
			this contains one or more <option> elements.

	*/
	class OLP_Question
	{

		protected $recommend;
		protected $options;

		public function __construct($recommend = NULL)
		{

			if (is_String($recommend)) $this->recommend = $recommend;

			$this->options = array();

		}

		public function Recommend($type = NULL)
		{

			if (is_string($type)) $this->recommend = $type;
			return($this->recommend);

		}

		/**

			@desc Add or return the options for this question.

			Not sure why it was done like this, but each
			question contains option elements, and each
			option element has a name attribute. In theory,
			then, a question could contain multiple HTML
			fields. In practice, I don't think this happens,
			but I left it as-is.

		*/
		public function Options($name = NULL, $option = NULL)
		{

			$options = FALSE;

			if (!is_null($name))
			{

				if (!is_null($option))
				{

					// check to see if we already have options
					// using this field name
					if (!array_key_exists($name, $this->options))
					{

						// create a new entry for this name
						if (is_array($option)) $this->options[$name] = $option;
						else $this->options[$name] = array($option);

					}
					else
					{

						// add to the entry for this name
						if (is_array($option)) $this->options[$name] = array_merge($this->options[$name], $option);
						else $this->options[$name][] = $option;

					}

					// return options for this name
					$options = $this->options[$name];

				}
				elseif (array_key_exists($name, $this->options))
				{
					// return options for this name
					$options = $this->options[$name];
				}

			}
			else
			{
				// return all options
				$options = $this->options;
			}

			return($options);

		}

		public function To_XML()
		{

			$xml = "<question recommend=\"{$this->recommend}\">";

			// get each field that has defined options
			foreach ($this->options as $name=>$options)
			{

				if (is_array($options) && count($options))
				{

					// get the options for this field name
					foreach ($options as $value)
					{
						$xml .= "<option name=\"{$name}\">".htmlentities($value)."</option>";
					}

				}
				else
				{
					$xml .= "<option name=\"{$name}\"/>";
				}

			}

			$xml .= "</question>";

			return($xml);

		}

		public function From_Simple_XML($simple)
		{

			$attr = $simple->attributes();

			if (array_key_exists('recommend', $attr))
			{
				$this->recommend = $attr['recommend'];
			}

			// get all option elements with
			// a name attribute
			$options = $simple->xpath('//option[@name]');

			foreach ($options as $element)
			{

				$attr = $element->attributes();
				$name = (string)$attr['name'];
				$option = (string)$element;

				$this->Options($name, $option);

			}

		}

	}

	class OLP_Signature extends XML_Data_Array
	{

		protected $base_element = 'signature';
		protected $data_element = 'data';
		protected $name_attribute = 'name';

		protected $allowed = array
		(
			'site_type',
			'page',
			'license_key',
			'promo_id',
			'promo_sub_code',
			'unique_id',
			'reason',
			'pwadvid',
			'tier'
		);

		public function __construct($data = NULL)
		{

			if (is_string($data))
			{
				parent::From_XML($data);
			}
			elseif (is_array($data))
			{
				parent::From_Array($data);
			}
			elseif (is_object($data))
			{
				parent::From_Simple_XML($data);
			}

		}

		public function From_EDS_Response(&$eds_response, $session_id)
		{

			$page = OLP_Pages::EDS_Page($eds_response);
			//$eds_response->page = $page;

			// set our local values
			$this->Value('page', $page);
			$this->Value('site_type', $eds_response->data['site_type']);
			$this->Value('license_key', $eds_response->data['license_key']);
			$this->Value('promo_id', $eds_response->data['promo_id']);
			$this->Value('promo_sub_code', $eds_response->data['promo_sub_code']);
			$this->Value('pwadvid', $eds_response->data['pwadvid']);
			if(is_string($session_id))
			{
				$this->Value('unique_id', $session_id);
			}
			if(isset($eds_response->data['tier']))
			{
				$this->Value('tier', $eds_response->data['tier']);
			}
			return;

		}

		/**

			@desc Override the default Valid function,
				and provide a little more specific validation.

		*/
		protected function Valid($name, $value = NULL)
		{

			$valid = parent::Valid($name, $value);

			if ($valid && ($name=='page'))
			{
				$pages = array('app_allinone', 'legal', 'preview_docs',
							   'app_completed', 'app_declined','lead_resell',
							   'ecash_react','agent_react_confirm');
				$valid = in_array($value, $pages);

			}

			return($valid);

		}

	}

	class OLP_Collection extends XML_Data_Array
	{

		protected $base_element = 'collection';
		protected $data_element = 'data';
		protected $subdata_element = 'subdata';
		protected $name_attribute = 'name';

		// used for validation
		protected $page = NULL;

		protected $by_page = array(

			'ALL' => array(
				// allowed on all pages
				'client_url_root',
				'client_ip_address',
			),

			'app_allinone' => array(

				// personal information
				'name_first',
				'name_last',
				'name_middle',
				'ssn_part_1',
				'ssn_part_2',
				'ssn_part_3',
				'state_id_number',
				'state_issued_id',
				'citizen',
				'date_dob_d',
				'date_dob_m',
				'date_dob_y',
				'military',

				// contact information
				'phone_home',
				'phone_work',
				'ext_work',
				'phone_cell',
				'phone_fax',
				'best_call_time',
				'email_primary',

				// residence information
				'residence_type',
				'home_street',
				'home_unit',
				'home_city',
				'home_state',
				'home_zip',

				// employment information
				'employer_length',
				'employer_name',

				// income information
				'income_type',
				'income_monthly_net',
				'income_frequency',
				'income_direct_deposit',
				'income_date1_d',
				'income_date1_m',
				'income_date1_y',
				'income_date2_d',
				'income_date2_m',
				'income_date2_y',
				'paydate', //Special field for paydate widget

				// banking information
				'bank_name',
				'bank_aba',
				'bank_account',
				'bank_account_type',
				'checking_account',

				// personal references
				'ref_01_name_full',
				'ref_01_phone_home',
				'ref_01_relationship',
				'ref_02_name_full',
				'ref_02_phone_home',
				'ref_02_relationship',
				'ref_03_name_full',
				'ref_03_phone_home',
				'ref_03_relationship',
				'ref_04_name_full',
				'ref_04_phone_home',
				'ref_04_relationship',
				
				'drivers_license_state',
				'date_of_hire',
				'work_title',
				'residence_start_date',
				'banking_start_date',
				
				'search_engine',
				'search_keywords',
				'vehicle_year',
				'vehicle_make',
				'vehicle_model',
				'vehicle_series',
				'vehicle_style',
				'vehicle_mileage',
				'vehicle_vin',
				'vehicle_value',
				'vehicle_color',
				'vehicle_license_plate',
				'vehicle_title_state',
				'customer_id',

				'offers',
				'legal_notice_1',
				'cali_agree',
				
				// loan information
				'loan_amount_desired',

				//UK
				'debit_card',

				//so they can force to specific vendor
				'ssforce',

				// debugging
				'no_checks',
				'fraud_scan',
				'use_tier',
				'exclude_tier', // GForge #5998 [DY]
				'datax_idv',
				'datax_perf',

				// Ecash specific
				'ecashapp',
				'agent_id',
				'promo_id',
				'ecashdn',
				'react_app_id',
				'fund_amount',
				'react_type',
				// Mantis #11944 -  card services will be passing this in now	[RV]
				'loan_type',

				//soft sell
				'ss_app_id'
			),

			'legal' => array(

				// esignature page
				'legal_approve_docs_1',
				'legal_approve_docs_2',
				'legal_approve_docs_3',
				'legal_approve_docs_4',
				'legal_agree',
				'esignature',

			),

		);

		protected $allowed = array();

		public function __construct($data = NULL, $page = NULL)
		{

			if (is_string($data))
			{
				$this->From_XML($data, $page);
			}
			elseif (is_array($data))
			{
				$this->From_Array($data, $page);
			}
			elseif (is_object($data))
			{
				$this->From_Simple_XML($data, $page);
			}

		}

		public function From_Array($array, $page = NULL)
		{

			$this->allowed = $this->Get_Allowed($page);
			$result = parent::From_Array($array);

			return($result);

		}

		public function From_XML($xml, $page = NULL)
		{

			$this->allowed = $this->Get_Allowed($page);
			$result = parent::From_XML($xml);

			return($result);

		}

		public function From_Simple_XML($simple, $page = NULL)
		{

			$this->allowed = $this->Get_Allowed($page);
			$result = parent::From_Simple_XML($simple);

			return($result);

		}

		public function From_EDS_Response(&$eds_response)
		{

			// get the page name
			$page = OLP_Pages::EDS_Page($eds_response);

			// get our data
			$data = &$eds_response->data;
			$this->From_Array($data, $page);

			return;

		}

		protected function Get_Allowed($page)
		{

			if (($page !== NULL) && isset($this->by_page[$page]) && is_array($this->by_page[$page]))
			{
				// get our allowed fields for this page
				$allowed = $this->by_page[$page];
			}

			// get fields allowed on all pages
			if (isset($this->by_page['ALL']) && is_array($this->by_page['ALL']))
			{
				if (!isset($allowed)) $allowed = array();
				$allowed = array_merge($allowed, $this->by_page['ALL']);
			}

			return($allowed);

		}


	}

	class OLP_Errors extends XML_Data_Array
	{

		protected $base_element = 'errors';
		protected $data_element = 'data';
		protected $name_attribute = 'name';

		protected $allowed = NULL;

		public function __construct($data = NULL)
		{

			if (is_string($data))
			{
				parent::From_XML($data);
			}
			elseif (is_array($data))
			{
				parent::From_Array($data);
			}
			elseif (is_object($data))
			{
				parent::From_Simple_XML($data);
			}

		}

		/**

			@desc Translate EDS errors to SOAP error codes:
				this is _really_ ugly, and should be fixed!

				Why, oh why, are we using different field names
				for the errors?

		*/
		public function From_EDS_Response(&$eds_response)
		{

			$err = new Error_Message_Resource();

			$error_codes = NULL;
			$errors = NULL;

			if (isset($eds_response->errors))
			{
				$error_codes = $eds_response->errors;
			}

			if (is_array($error_codes))
			{

				$errors = array();

				foreach ($error_codes as $field)
				{

					$desc = $err->Get_Error_Desc($field);

					if (preg_match('/^Your Electronic Signature/', $field))
					{
						$desc = $field;
						$field  = 'esignature';
					}
					elseif (preg_match('/^You must make at least/', $field))
					{
						$desc = $field;
						$field = 'income_monthly_net';
					}
					elseif (preg_match('/^Invalid pay span/', $field))
					{
						$desc = $field;
						$field = 'income_frequency';
					}
					elseif (preg_match('/^Not enough time at this job/', $field))
					{
						$desc = $field;
						$field = 'employer_length';
					}
					elseif (preg_match('/^Bank Account Warning:/', $field))
					{
						$desc = $field;
						$field = 'bank_account';
					}
					elseif (preg_match('/^Not enough time/', $field))
					{
						$desc = $field;
						$field = 'employer_length';
					}
					elseif ($field=='dob')
					{
						$field = array('date_dob_y', 'date_dob_m', 'date_dob_d');
					}
					elseif ($field=='best_call_time')
					{
						$desc = 'Please indicate when you would prefer to be contacted.';
					}
					elseif (substr($field, 0, 9)=='pay_date1')
					{
						if ($field == 'pay_date1') $desc = 'Please enter your next two paydates.';
						$field = array('income_date1_y', 'income_date1_m', 'income_date1_d');
					}
					elseif (substr($field, 0, 9)=='pay_date2')
					{
						if ($field == 'pay_date2') $desc = 'Please enter your next two paydates.';
						$field = array('income_date2_y', 'income_date2_m', 'income_date2_d');
					}
					elseif (preg_match('/^ssn_part_\d$/', $field))
					{
						$desc = 'Social security number is a required field.';
					}
					elseif ($field == 'too_many_twice_monthly' || $field == 'too_many_monthly')
					{
						$field = array('income_date1_y', 'income_date1_m', 'income_date1_d',
							'income_date2_y', 'income_date2_m', 'income_date2_d');
					}
					elseif ($field == 'cali_agree_conditional')
					{
						$field = 'cali_agree';
					}
					elseif ($field == 'legal_agree')
					{
						$desc = 'You must agree to the legal terms to continue.';
					}
					elseif ($field == 'income_type')
					{
						$desc = 'Please specify your source of income.';
					}
					elseif ($field == 'social_security_number')
					{
						$field = NULL;
					}
					elseif ($field == 'paydate')
					{
						$desc = 'The paydate model is required for this type';
					}
					elseif ($field == 'frequency')
					{
						$desc = 'You must indicate how often you are paid';
					}
					elseif ($field == 'weekly_day')
					{
						$desc = 'You must indicate what day you are paid';
					}
					elseif ($field == 'biweekly_day')
					{
						$desc = 'You must indicate what day you are paid';
					}
					elseif ($field == 'biweekly_date')
					{
						$desc = 'You must choose the most recent pay date';
					}
					elseif ($field == 'twicemonthly_type')
					{
						$desc = 'You must choose whether you are paid on a date or day of the week';
					}
					elseif ($field == 'twicemonthly_date1')
					{
						$desc = 'You must indicate your 1st paydate for the month';
					}
					elseif ($field == 'twicemonthly_date2')
					{
						$desc = 'You must indicate your 2nd paydate for the month';
					}
					elseif ($field == 'twicemonthly_week')
					{
						$desc = 'You must indicate which weeks you get paid each month';
					}
					elseif ($field == 'twicemonthly_day')
					{
						$desc = 'You must indicate what day you are paid';
					}
					elseif ($field == 'twicemonthly_order')
					{
						$desc = 'The second paydate must be later than the first paydate';
					}
					elseif ($field == 'monthly_type')
					{
						$desc = 'You must indicate when you are paid each month';
					}
					elseif ($field == 'monthly_date')
					{
						$desc = 'You must indicate which day you are paid each month';
					}
					elseif ($field == 'monthly_week')
					{
						$desc = 'You must choose which week of the month you are paid';
					}
					elseif ($field == 'monthly_day')
					{
						$desc = 'You must choose which day of the week you are paid';
					}
					elseif ($field == 'monthly_after_date')
					{
						$desc = 'You must choose which day of the week you are paid';
					}
					elseif ($field == 'monthly_after_day')
					{
						$desc = 'You must indicate the appropriate day';
					}
					elseif($field == 'fund_amount')
					{
						$desc = 'Please select a valid fund amount.';
					}
					elseif($field == 'work_title')
					{
						$desc = 'You must provide a work title.';
					}
					elseif($field == 'state_issued_id')
					{
						$desc = 'You must provide a driver\'s license state.';
					}
					elseif($field == 'vehicle_title_state')
					{
						$desc = 'You must provide a title state for the vehicle.';
					}
					elseif($field == 'vehicle_vin')
					{
						$desc = 'You must provide a valid VIN number.';
					}
					elseif($field == 'vehicle_license_plate')
					{
						$desc = 'You must provide the vehicle\'s license plate.';
					}
					elseif($field == 'vehicle_make')
					{
						$desc = 'You must provide the vehicle\'s make.';
					}
					elseif($field == 'vehicle_mileage')
					{
						$desc = 'You must provide the vehicle\'s mileage.';
					}
					elseif($field == 'vehicle_model')
					{
						$desc = 'You must provide the vehicle\'s model.';
					}
					elseif($field == 'vehicle_series')
					{
						$desc = 'You must provide the vehicle\'s series.';
					}
					elseif($field == 'vehicle_year')
					{
						$desc = 'You must provide the vehicle\'s year.';
					}
					elseif($field == 'vehicle_style')
					{
						$desc = 'You must provide the vehicle\'s style.';
					}
					elseif($field == 'vehicle_color')
					{
						$desc = 'You must provide the vehicle\'s color.';
					}
					elseif($field == 'banking_start_date')
					{
						$desc = 'You must provide a banking start date.';
					}
					elseif($field == 'residence_start_date')
					{
						$desc = 'You must provide a residence start date.';
					}
					elseif($field == 'date_of_hire')
					{
						$desc = 'You must provide a date of hire.';
					}
					elseif($field == 'vehicle_value')
					{
						$desc = 'We could not find a vehicle matching the supplied information.';
					}
					if (is_array($field))
					{
						foreach ($field as $name) $errors[$name] = $desc;
					}
					elseif ($field)
					{
						$errors[$field] = $desc;
					}

				}

				// rearrange the errors into the same order as the data
				$temp = array_intersect(array_keys($eds_response->data), array_keys($errors));
				$errors = array_merge(array_flip($temp), $errors);

				$this->From_Array($errors);

			}

			return;

		}

	}

?>
