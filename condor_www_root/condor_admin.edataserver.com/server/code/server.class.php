<?php

// Get common functions
require_once(LIB_DIR . "common_functions.php");

// Logging
require_once(DIR_LIB . "applog.1.php");
require_once(DIR_LIB . 'applog.controller.01.php');

// Timer
require_once(DIR_LIB . "timer.class.php");

// Session class
require_once(DIR_LIB . "session.8.php");

// ACL
require_once(LIB_DIR . "acl.3.php");

// Authentication
require_once(DIR_LIB . "security.6.php");

// Site Type Manager
require_once(LIB_DIR . "site_type_client.php");

// Data Prep
require_once(LIB_DIR . "data_preparation.php");

class Server
{
	public $session;
	public $log;
	public $lognew;
	public $company;
	public $company_id;
	public $company_list;
	public $system_id;
	public $agent_id;
	public $login;
	public $agent_name;
	public $acl;
	public $transport;
	public $timer;
	public $php_memory_usage;
	public $active_id;
	public $site_type;
	public $site_type_obj;
	public $template_obj;
	public $document_obj;
	public $files;
	public $api_auth;
	public $company_short;

	private $mysqli;
	private $attribute_state_list;
	private $active_module;

	/**
	 *
	 */
	public function __construct($session_id = FALSE)
	{
		if (defined("SCRIPT_TIME_LIMIT_SECONDS"))
		{
			set_time_limit(SCRIPT_TIME_LIMIT_SECONDS);
		}

		$this->attribute_state_list = array('active_module','company', 'company_id','agent_id',
		'user_acl','login', 'agent_name', 'company_list',
		'system_id', 'active_id', 'site_type', 'site_type_obj','template_obj','document_obj',
		'api_auth','company_short');

		$this->company_list = array();
		$this->transport = new Transport();

		$session_id = ($session_id) ? $session_id : $this->Create_Session_Id();
		$this->session = new Session_8($this->MySQL(), 
			DB_NAME, 
			'session', 
			$session_id,
			'ssid',
			'gz',
			TRUE);

		$this->Fetch_Attribute_State();

		$context = ( isset($_SESSION["Server_state"]["company"]) ) ? $_SESSION["Server_state"]["company"] : "";
		
		$this->log = new Applog(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, strtoupper($context));
		$this->lognew = new Applog_Controller_01(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, strtoupper($context));
		$this->lognew->Set_Dynamic_Config_Building( true );
		$this->lognew->Set_This_Special_Val( 'ipaddress', $_SERVER['REMOTE_ADDR'] );
		$this->lognew->Set_This_Special_Val( 'agentid', $this->agent_id );
		$this->lognew->Set_This_Special_Val( 'agent_name', $this->agent_name );
		$this->lognew->Set_This_Special_Val( 'company', $this->company );
		$this->lognew->Set_This_Special_Val( 'company_id', $this->company_id );
		                                                                             

		$this->timer = new Timer($this->log);

		// Trap condition where PHP memory limit is reached or approached and write log entry
		/*$this->php_memory_usage = memory_get_usage();
		if ($this->php_memory_usage > PHP_MEMORY_USE_THRESHOLD)
		{
			if ( empty($_SESSION["php_memory_usage_threshold_reached"]) )
			{
				$_SESSION["php_memory_usage_threshold_reached"] = $this->php_memory_usage;
				$this->log->Write("PHP Memory usage reached " . $this->php_memory_usage . " bytes.", LOG_DEBUG);
				$this->log->Write("SESSION recursive count: " . count($_SESSION, COUNT_RECURSIVE) . ".", LOG_DEBUG);
				$this->log->Write("Server state data follows...", LOG_DEBUG);
				$this->log->Write("\n" . print_r($_SESSION["Server_state"], TRUE) . "\n", LOG_DEBUG);
			}
		}*/
	}

	/**
	 *
	 */
	public function __destruct()
	{
		$this->Save_Attribute_State();
	}


	/**
	 *
	 */
	private function Save_Attribute_State()
	{
		foreach($this->attribute_state_list as $attribute)
		{
			if( ! empty($this->{$attribute}) )
			{
				$_SESSION[get_class($this) . "_state"][$attribute] = $this->{$attribute};
			}
			else 
			{
				unset($_SESSION[get_class($this) . "_state"][$attribute]);	
			}
		}
		return TRUE;
	}
	
	/**
	 *
	 */
	public function Reset_Active_ID_State()
	{
		unset($this->active_id);
		return TRUE;
	}

	/**
	 *
	 */
	private function Fetch_Attribute_State()
	{
		
		foreach($this->attribute_state_list as $attribute)
		{
			if( isset($_SESSION[get_class($this) . "_state"][$attribute]) )
			{
				$this->{$attribute} = $_SESSION[get_class($this) . "_state"][$attribute];
			}
		}
		return TRUE;
	}

	/**
	 * public function MySQLi()
	 */
	public function MySQLi()
	{
		require_once("mysqli.1.php");

		if( !is_a($this->mysqli, "MySQLi_1") )
		{
			$this->mysqli = new MySQLi_1(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
			//$this->Set_Time_Zone();
		}

		return $this->mysqli;
	}
	
	/**
	 * public function MySQL()
	 */
	public function MySQL()
	{
		require_once("mysql.4.php");
		
		$host = (is_numeric(DB_PORT)) ? DB_HOST.':'.DB_PORT : DB_HOST;

		$this->sql = new MySQL_4($host, DB_USER, DB_PASS);
		$this->sql->Connect();
		
		return $this->sql;
	}

	/**
	 *
	 */
	public function Set_Time_Zone()
	{
		//if this fails run:
		//shell> mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root mysql
		$set = "set time_zone = " . TIME_ZONE;
		$this->mysqli->Query($set);
	}


	/**
	 *
	 */
	public function Fetch_Company_List()
	{
		$rows = $this->acl->Get_Companies();

		foreach($rows as $row)
		{/*
			if( is_string($this->company)  && (strtolower(trim($this->company)) == strtolower(trim($row->name_short))) )
			{
				$this->company_id = $row->company_id;
			}*/

			$this->company_list[$row->company_id]['name_short'] = $row->name_short;
			$this->company_list[$row->company_id]['name'] = $row->name;
		}

		return TRUE;
	}

	/**
	 *
	 */
	private function Validate_Module($name)
	{

		if( $this->acl->Acl_Access_Ok($name, $this->company_id))
		{
			return true;
		}
		else
		{
			$error = "Insufficient permissions to access {$name} module.";
			throw new Exception($error);
		}


	}


	/**
	 *
	 */
	private function Load_Module($name, $request)
	{
		if($this->Validate_Module($name))
		{
			
			$module = ($this->transport->section_manager->parent_module) ? $this->transport->section_manager->parent_module : $name;
			$module_file = SERVER_MODULE_DIR . $module . "/server_module.class.php";

			include_once($module_file);
			
			$module = Server_Module::Get_Server_Module($this, $request, $name);
			return $module->Main();
		}

		return FALSE;
	}


	/**
	 *
	 */
	public function Process_Data($request)
	{

		$acl_sub_access = '';
		$user_acl_sub_names = '';

		// reset active ids
		if ($request->reset_state)
		{
			$this->Reset_Active_ID_State();
		}
		
		try
		{
			if( !empty($request->logout) )
			{

				$this->transport->Set_Levels('login');
				session_destroy();
				return $this->transport;
			}

			//			if( !(isset($this->agent_id) && isset($this->user_acl)) )
			if( !isset($this->agent_id) )
			{
				if( isset($request->page) && $request->page == "login" )
				{
					$this->acl = new ACL_3($this, $this->MySQLi());
					$security = new Security($this->MySQLi(), SESSION_EXPIRATION_HOURS);
					
					if( $security->Login_User(SYSTEM_NAME_SHORT, $request->login, $request->password, $_SESSION['security_6']['login_time']) )
					{
						$this->company_id = $security->Get_Company_ID();
						$this->Fetch_Company_List();
						$this->company = $this->company_list[$this->company_id]['name_short'];
						Set_Company_Constants($this->company);						
						
						$this->agent_id = $security->Get_Agent_ID();
						$this->system_id = $security->Get_System_ID();
						$this->company_short = $security->Get_Company_Short();
						$this->api_auth = $security->Get_API_Auth();
						$this->login = $request->login;
						$this->agent_name = $security->Get_Name_First() . " " . $security->Get_Name_Last();
						$this->acl->Set_System_Id($this->system_id);
						$this->acl->Fetch_User_ACL($this->agent_id, $this->company_id);
						$this->transport->Set_Levels('application');
						
						$this->Set_Acl_Vars();
					}
					else
					{
						$this->transport->Add_Error("Invalid login");
						$this->transport->Set_Levels('login');
					}
				}
				else
				{
					$this->transport->Set_Levels('login');
				}
			}
			else
			{
				
				Set_Company_Constants($this->company);

				$this->acl = new ACL_3($this, $this->MySQLi());
				$this->acl->Set_System_Id($this->system_id);
				$this->acl->Fetch_User_ACL($this->agent_id, $this->company_id);
				
				$security = new Security_6($this->MySQLi(), SESSION_EXPIRATION_HOURS);

				if( !$security->Check_Timeout($_SESSION['security_6']['login_time']) )
				{
					$this->transport->Add_Error("Login expired");

					$this->transport->Set_Levels('login');

					session_destroy();
				}

				else
				{
					$this->Set_Acl_Vars();

					if( isset($request->module) )
					{
						$this->active_module = $request->module;
					}

					if (isset($this->active_module))
					{
					
						$this->transport->section_manager = new Section_Manager($this->transport, $this->active_module);
						
						$this->transport->Set_Levels('application');
						
						$module_result = $this->Load_Module($this->active_module, $request);
						
						$this->transport->acl_sub_access = $this->acl->Get_Acl_Access($this->active_module);
						
						$this->transport->user_acl_sub_names = $this->acl->Get_Acl_Names($this->transport->acl_sub_access);
						$this->transport->section_views = $this->acl->Get_Section_Views($this->company_id, $this->active_module);
										
					}
					else
					{
						
						$this->transport->Set_Levels('application');
					}
				}
			}

		}
		catch( Exception $e)
		{
			$err_text = "Exception: " . To_String($e) . "\n";

			$this->log->Write($err_text, LOG_ERR);

			$this->transport->Set_Levels("exception");
		}

		$this->Save_Attribute_State();
		return $this->transport;
	}

	/**
	 *
	 */
	public function __toString()
	{
		$string = "<pre>";
		$string .= "agent_id = {$this->agent_id}\n";
		$string .= "acl = " . To_String($this->acl);
		$string .= "successful_modules = " . To_String($this->successful_modules);
		$string .= "active_module = {$this->active_module}\n";
		$string .= "</pre>";

		return $string;
	}
	
	private function Set_Acl_Vars()
	{
		$acl_descriptions = $this->acl->Get_Acl_Access();
		$this->transport->user_acl = $acl_descriptions;
		$this->transport->user_acl_names = $this->acl->Get_Acl_Names($acl_descriptions);
		$this->transport->acl_sorted = $this->acl->Get_Acl_Sorted($acl_descriptions);
		$this->transport->acl_unsorted = $this->acl->Get_Acl_Un_Sorted($acl_descriptions);
		$this->transport->agent_id = $this->agent_id;
		$this->transport->login = $this->login;
		$this->transport->company = $this->company;
		$this->transport->company_id = $this->company_id;
	}
	
	/**
	* @return string
	* @desc Create a session id cause for some reason one was not passed in.
	*/
	private function Create_Session_Id()
	{
		return md5(microtime());
	}
}

