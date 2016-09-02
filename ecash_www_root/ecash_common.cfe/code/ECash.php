<?php

/**
 * ECash center class.  Contains mostly anything which could be
 * considered 'state info'. We're trying to keep this as minimal as
 * possible
 *
 * @author Justin Foell <justin.foell@sellingsource.com>
 * @author John Hargrove <john.hargrove@sellingsource.com>
 */
class ECash
{

	/*
	 *  eCash System const
	 */
	const SYSTEM_NAME = 'ecash3_0';	
	
	/**
	 * @var ECash_Models_Application
	 */
	private static $application;

	/**
	 * @var ECash_Models_Company
	 */
	private static $company;

	/**
	 * @var ECash_Models_Agent
	 */
	private static $agent;
	
	/**
	 * @var ECash_Transport
	 */
	private static $transport;	
	
	/**
	 * @var ECash_Module
	 */
	private static $module;	

	/**
	 * @var ECash_Transport
	 */
	private static $acl;	
		
	/**
	 * @var ECash_Request
	 */
	private static $request;	

	/**
	 * @var ECash_Monitoring
	 */	
	private static $monitoring;

	private static $context;
	
	/**
	 * @var ECash_Config
	 */
	private static $config;

    /**
     * @var DB_IDatabase_Connection array
     */
    private static $dbconnections;


    /**
     * ECash Server object
     * 
     * @var Server
     */
    private static $server;
    
	/**
	 * Sets the configuration for the application
	 *
	 * @param ECash_Config $config
	 * @deprecated This should only be used in transitionary code.
	 */
	public static function setConfig(ECash_Config $config)
	{
		self::$config = $config;
	}
	
	/**
	 * Returns the configuration for the application
	 *
	 * @return ECash_Config
	 * @deprecated This should only be used in transitionary code.
	 */
	public static function getConfig()
	{
		if (!isset(self::$config))
		{
			throw new BadMethodCallException("The configuration has not been set. ".
				"Please call ECash::setConfig() before any calls to ECash::getConfig()");
		}
		return self::$config;
	}
	
	/**
	 * Returns a connection from the configuration using the given $config_key
	 *
	 * @param string $config_key
	 * @return DB_IConnection_1
	 */
	public static function getDb($config_key)
	{
		if(empty(self::$dbconnections[$config_key]))
		{
			self::$dbconnections[$config_key] = self::$config->getDbConfig($config_key)->getConnection();
		}
		return 	self::$dbconnections[$config_key];
	}
	
	/**
	 * A convenience function to return the master database.
	 *
	 * @return DB_IConnection_1
	 */
	public static function getMasterDb()
	{
		return self::getDb(ECash_Config::DB_MASTER_ID);
	}
	
	/**
	 * A convenience function to return the read-only slave database.
	 *
	 * @return DB_IConnection_1
	 */
	public static function getSlaveDb()
	{
		return self::getDb(ECash_Config::DB_SLAVE_ID);
	}

	/**
	 * A convenience function to return the MSSQL DB connection.
	 *
	 * @return DB_IConnection_1
	 */
	public static function getAppSvcDB()
	{
		return self::getDb(ECash_Config::DB_APPSERVICE_ID);
	}

	/**
	 * A convenience function to return the State Object DB connection.
	 *
	 * @return DB_IConnection_1
	 */
	public static function getStateObjectDB()
	{
		return self::getDb(ECash_Config::DB_STATEOBJECT_ID);
	}

	/**
	 * @param ECash_Models_Application $app (current)
	 * @param ECash_Application $app (future)
	 */
	//public static function setApplication(ECash_Application $app)
	public static function setApplication(ECash_Models_Application $app)
	{
		self::getApplicationById($app->application_id);
	}

	/**
	 * Returns the currently loaded application
	 *
	 * @return ECash_Models_Application
	 */
	public static function getApplication()
	{
		return self::$application;
	}

	/**
	 * Sets the current company model
	 *
	 * @param ECash_Models_Company $company (current)
	 * @param ECash_Company $company (future)
	 */
	//public static function setCompany(ECash_Company $company)
	public static function setCompany(ECash_Company $company)
	{
		if(empty(self::$company) || self::$company->name_short != $company->name_short)
		{
			self::$company = $company;
			if(defined('CUSTOMER_CODE_DIR') && defined('ECASH_EXEC_MODE'))
			{
				$enterprise_prefix = ECash::getConfig()->ENTERPRISE_PREFIX;
				require_once(CUSTOMER_CODE_DIR . "{$enterprise_prefix}/Config/{$company->name_short}.php");
				$company_config = strtoupper($company->name_short).'_CompanyConfig';
				ECash::setConfig(new $company_config(ECash::getConfig()->getBaseConfig()));
			}
		}
	}
	
	/**
	 * Returns the model for the current logged in company.
	 *
	 * @return ECash_Models_Company
	 */
	public static function getCompany()
	{
		return self::$company;
	}

	/**
	 * Sets the current logged in agent
	 *
	 * @param ECash_Models_Agent $agent (current)
	 * @param ECash_Agent $agent (future)
	 */
	//public static function setAgent(ECash_Agent $agent)
	public static function setAgent(ECash_Agent $agent)
	{
		self::$agent = $agent;
	}

	/**
	 * Return the current logged in agent
	 *
	 * @return ECash_Models_Agent
	 */
	public static function getAgent()
	{
		if(empty(self::$agent))
		{
			return 	self::getAgentById(eCash::getConfig()->DEFAULT_AGENT_ID);
		}
		else
		{
			return self::$agent;
		}
	}

	/**
	 * Return the transport object (run away)
	 *
	 * @return ECash_Transport
	 */
	public static function getTransport()
	{
		if (!self::$transport instanceof ECash_Transport)
		{
			self::$transport = self::getFactory()->getTransport();
		}
		
		return self::$transport;
	}		

	/**
	 * Return the ACL object (run away)
	 *
	 * @return ECash_ACL
	 */
	public static function getACL(DB_IConnection_1 $db = NULL)
	{
		if (!self::$acl instanceof ECash_ACL)
		{
			self::$acl = self::getFactory()->getACL($db);
			self::$acl->setSystemId(self::getSystemId());
		}
		
		return self::$acl;
	}			
	
	/**
	 * Return the Module object (run away)
	 *
	 * @return ECash_Module
	 */
	public static function getModule()
	{
		if (!self::$module instanceof ECash_Module)
		{
			self::$module = self::getFactory()->getModule();
		}
		
		return self::$module;
	}			
		
	/**
	 * Return the Module object (run away)
	 *
	 * @return ECash_Module
	 */
	public static function getRequest()
	{
		if (!self::$request instanceof ECash_Request)
		{
			self::$request = self::getFactory()->getRequest();
		}
		
		return self::$request;
	}			
			
	public static function getMonitoring()
	{
		if (!self::$monitoring instanceof ECash_Monitoring)
		{
			self::$monitoring = self::getFactory()->getMonitoringManager();
		}
		
		return self::$monitoring;		
	}
	
	/**
	 * WTF is this?
	 *
	 * @return Server
	 */
	public static function getServer()
	{
		if(isset(self::$server))
		{
			return self::$server;
		}

		//not sure if this should get 'set' into self::$server or not
		if(!empty($_SESSION['server']))
			$server = $_SESSION['server'];

		if (empty($server))
		{
			$request = self::getRequest();
			$server = Server_Factory::get_server_class(
				isset($request->api) ? $request->api : null,
				isset($request->session_id) ? $request->session_id : NULL);
		}
		
		return $server;
	}

	/**
	 * Setter method to put the ECash Server object
	 * in the ECash object.
	 *
	 * @param Server $server
	 */
	public static function setServer(Server $server)
	{
		/**
		 * I know you're probably saying, WTF?!  This is to get the Server
		 * object out of $_SESSION, which is already really bloated.  There's
		 * also no reason at all for us to save the Server object in the DB
		 * since it gets recreated and re-set on every request. [BR]
		 */
		self::$server = $server;
	}
	
	
	/**
	 * Returns an instance of ECash_Factory
	 * 
	 * @return ECash_Factory
	 */
	public static function getFactory()
	{ 
		return self::$config->FACTORY;
	}

	/**
	 * Gets (hopefully cached) ECash_Models_Application object based on ID
	 * 
	 * @param int $application_id
	 * @param DB_IConnection_1 $database
	 * @param bool $force_reload Do not used cached instance of Application
	 * @param bool $use_observer Use the Web Service observer
	 * @return ECash_Model_Application
	 */
	public static function getApplicationById($application_id, DB_IConnection_1 $database = NULL, $force_reload = FALSE, $use_observer = TRUE)
	{
		/** FOR BUSINESS OBJECTS
		 * */
		if(self::$application != NULL && self::$application->exists() && self::$application->application_id == $application_id && $force_reload === FALSE)
		{
			return self::$application;
		}

		$company_id = self::getCompany() ? self::getCompany()->company_id : NULL;
		
		self::$application = self::getFactory()->getApplication(
			$application_id,
			$company_id,
			$database,
			$use_observer);
		
		return self::$application;
	}

	/**
	 * @return System ID
	 */	
	public static function getSystemId()
	{
		return 3;
		return self::getFactory()->getReferenceList('System')->toId(SYSTEM_NAME);		
	}

	/**
	 * Gets the current instance of the CFE engine
	 *
	 * @return ECash_CFE_Engine
	 */
	public static function getEngine()
	{
		return self::$application->getEngine();
	}
	
	/**
	 * Fetch customer object using SSN
	 *
	 * @param string $ssn
	 * @param DB_IConnection_1 $database
	 * @return ECash_Customer
	 */
	public static function getCustomerBySSN($ssn, DB_IConnection_1 $database = NULL)
	{
		return self::getFactory()->getCustomerBySSN($ssn, self::getCompany()->company_id, $database);
	}

	/**
	 * Fetch customer object using ID
	 *
	 * @depricated maybe? could be called from Factory directly
	 * @param int $customer_id
	 * @param DB_IConnection_1 $database
	 * @return ECash_Customer
	 */
	public static function getCustomerById($customer_id, DB_IConnection_1 $database = NULL)
	{
		return self::getFactory()->getCustomerByID($customer_id, self::getCompany()->company_id, $database);
	}

	/**
	 * Fetch customer object using Application ID
	 *
	 * @depricated maybe? could be called from Factory directly
	 * @param int $application_id
	 * @param DB_IConnection_1 $database
	 * @return ECash_Customer
	 */
	public static function getCustomerByApplicationId($application_id, DB_IConnection_1 $database = NULL)
	{
		return self::getFactory()->getCustomerByApplicationId($application_id, self::getCompany()->company_id, $database);
	}

	/**
	 * Fetch agent object using agent_id
	 *
	 * @param int $agent_id
	 * @param DB_IConnection_1 $database
	 * @return ECash_Agent
	 */
	public static function getAgentById($agent_id, DB_IConnection_1 $database = NULL)
	{
		return self::getFactory()->getAgentById($agent_id, $database);
	}

	/**
	 * Grabs the current ecash logging device
	 *
	 * @return Log_ILog_1
	 */
	public static function getLog($Log_Name = null)
	{
		return self::getFactory()->getLog($Log_Name);
	}
	/**
	 * Fetch agent object using login info (useful for login page)
	 *
	 * @param string $system_name_short
	 * @param string $login
	 * @param DB_IConnection_1 $database
	 * @return ECash_Agent
	 */
	public static function getAgentBySystemLogin($system_name_short, $login, DB_IConnection_1 $database = NULL)
	{
		return self::getFactory()->getAgentBySystemLogin($system_name_short, $login, $database);
	}
}

?>
