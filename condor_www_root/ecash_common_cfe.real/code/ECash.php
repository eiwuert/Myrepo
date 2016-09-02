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
	 * @param ECash_Models_Application $app (current)
	 * @param ECash_Application $app (future)
	 */
	//public static function setApplication(ECash_Application $app)
	public static function setApplication(ECash_Models_Application $app)
	{
		self::$application = $app;
		
		self::$context = new ECash_CFE_DefaultContext(
			self::$application,
			DB_DatabaseConfigPool_1::getConnection(ECash_Models_DatabaseInstanceHandler::ALIAS_MASTER),
			array()
			);

		// attach to the new application
		$app_observer = new ECash_CFE_ApplicationModelObserver();
		$app_observer->attach(self::$application);
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
		self::$company = $company;
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
			return 	self::getAgentById(eCash_Config::getInstance()->DEFAULT_AGENT_ID);
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
	 * @return unknown
	 */
	public function getServer()
	{
		return $_SESSION['server'];
	}

	/**
	 * Returns an instance of ECash_Factory
	 * 
	 * @return ECash_Factory
	 */
	public static function getFactory()
	{
		return ECash_Config::getInstance()->FACTORY;
	}

	/**
	 * Gets (hopefully cached) ECash_Models_Application object based on ID
	 * 
	 * @param int $application_id
	 * @param DB_IConnection_1 $database
	 * @return ECash_Model_Application
	 */
	public static function getApplicationById($application_id, DB_IConnection_1 $database = NULL, $force_reload = FALSE)
	{
		/** FOR BUSINESS OBJECTS
		 * */
		if(self::$application != NULL && self::$application->application_id == $application_id && $force_reload === FALSE)
		{
			return self::$application;
		}

		self::$application = self::getFactory()->getApplication($application_id, self::getCompany()->company_id, $database);
		
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
//        $f = new ECash_CFE_RulesetFactory(DB_DatabaseConfigPool_1::getConnection(ECash_Models_DatabaseInstanceHandler::ALIAS_MASTER));
//		$engine = ECash_CFE_Engine::getInstance(self::$context);
//		$engine->setRuleset($f->fetchRuleset(self::$application->cfe_rule_set_id));
//		return $engine;
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
