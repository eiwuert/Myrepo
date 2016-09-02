<?php

require_once 'libolution/Object.1.php';
require_once 'libolution/DB/MySQLConfig.1.php';
require_once 'libolution/DB/DatabaseConfigPool.1.php';
require_once 'Models/WritableModel.php';
require_once 'Factory.php';


/**
 * A Base class to manage multiple ecash configurations.
 *
 *
 * Example usage:
 *
 * ECash_Config::useConfig('CLKConfig', 'LiveEnvironment');
 *
 * -- execute a whole bunch of code --
 * $companyName = ECash_Config::getInstance()->company_name;
 * $executionMode = ECash_Config::getInstance()->execution_mode;
 *
 *
 * class BaseConfig extends ECash_Config
 * {
 * 	protected function init()
 * 	{
 * 		parent::init();
 *
 * 		$this->configVariables['var1'] = 'blahblah';
 * 		$this->configVariables['var2'] = 'test';
 * 	}
 * }
 *
 * class ImpactConfig extends BaseConfig
 * {
 * 	protected function init()
 * 	{
 * 		parent::init();
 *
 * 		$this->configVariables['company_name'] = 'Impact';
 * 	}
 * }
 *
 * class CLKConfig extends BaseConfig
 * {
 * 	protected function init()
 * 	{
 * 		parent::init();
 *
 * 		$this->configVariables['company_name'] = 'CLK';
 * 	}
 * }
 *
 * class LiveEnvironment extends ECash_Config
 * {
 *	protected function init()
 * 	{
 * 		parent::init();
 *
 * 		$this->configVariables['execution_mode'] = 'live';
 * 	}
 * }
 *
 */
abstract class ECash_Config extends Object_1
{
	/**
	 * A singleton instance of the config class
	 *
	 * @var ECash_Config
	 */
	static private $instance;

	/**
	 * A singleton instance of the factory class
	 */
	private static $factory;

	/**
	 * A singleton instance of the config class
	 *
	 * @var ECash_Config
	 */
	static private $config_name;

	/**
	 * A singleton instance of the config class
	 *
	 * @var ECash_Config
	 */
	static private $environment_name;

	/**
	 * An array containing all ecash configuration variables
	 *
	 * @var Array
	 */
	protected $configVariables;

	/**
	 * A base configuration that is being (optionally) decorated with the
	 * current configuration.
	 *
	 * @var ECash_Config
	 */
	protected $baseConfig;

	/**
	 * Where we keep our database connections.  We have the connection pool, but
	 * it doesn't handle setting collation and the time zones.
	 * @var array
	 */
	static private $db_connection_pool = array();
	
	/**
	 * Create a new ecash config object. This should never be called directly.
	 * Use getInstance() instead
	 *
	 * @param ECash_Config $baseConfig
	 * @see ECash_Config::getInstance()
	 */
	protected function __construct(ECash_Config $baseConfig = null)
	{
		$this->baseConfig = $baseConfig;
		$this->init();
	}

	/**
	 * Override this function to set the various configuration options of
	 * the class.
	 *
	 */
	abstract protected function init();

	private static function initDB()
	{
		$config_master = new DB_MySQLConfig_1(ECash_Config::getInstance()->DB_HOST,
									   ECash_Config::getInstance()->DB_USER,
									   ECash_Config::getInstance()->DB_PASS,
									   ECash_Config::getInstance()->DB_NAME,
									   ECash_Config::getInstance()->DB_PORT);

		$config_slave = new DB_MySQLConfig_1(ECash_Config::getInstance()->SLAVE_DB_HOST,
									   ECash_Config::getInstance()->SLAVE_DB_USER,
									   ECash_Config::getInstance()->SLAVE_DB_PASS,
									   ECash_Config::getInstance()->SLAVE_DB_NAME,
									   ECash_Config::getInstance()->SLAVE_DB_PORT);

		DB_DatabaseConfigPool_1::add(ECash_Models_WritableModel::ALIAS_MASTER, $config_master);
		DB_DatabaseConfigPool_1::add(ECash_Models_WritableModel::ALIAS_SLAVE, $config_slave);
	}

	/**
	 * Sets the configuration to be used for the remainder of the request.
	 * The first parameter is the class name of the company configuration to
	 * use and the second parameter is for the environment configuration class
	 * name.
	 *
	 * @param string $environment
	 * @param string $config
	 */
	static public function useConfig($environment, $config = NULL)
	{
//		if (isset(self::$instance) && $config == self::$config_name)
//		{
//			throw new Exception("Config already set. Multiple calls to useConfig() are not currently allowed.");
//		}

		if($config === NULL)
		{
			self::$instance = self::createInstance($environment, null);
		}
		else
		{
			$baseConfig = self::createInstance($config);
			self::$instance = self::createInstance($environment, $baseConfig);
		}

		self::$environment_name = $environment;
		self::$config_name = $config;
		self::initDB();
	}

	/**
	 * Creates a new instance of a configuration class. If the second
	 * parameter is passed then the new instance will decorate the
	 * second parameter.
	 *
	 * @param string $className
	 * @param ECash_Config $baseConfig
	 * @return ECash_Config
	 */
	static private function createInstance($className, ECash_Config $baseConfig = null)
	{
		//if (is_null($className))
		//{
		//	throw new Exception("NULL configuration class name passed");
		//}

		if (!class_exists($className))
		{
			throw new Exception("$className class does not exist");
		}

		$classRefl = new ReflectionClass($className);
		if (!$classRefl->isSubclassOf(__CLASS__))
		{
			throw new Exception("$className is not a child of ". __CLASS__);
		}

		return new $className($baseConfig);
	}

	/**
	 * Returns the configuration object for the current request.
	 *
	 * @return ECash_Config
	 */
	static public function getInstance()
	{
		if (!isset(self::$instance))
		{
			throw new Exception("The configuration has not been set. ".
				"Please call useConfig before any calls to getInstance()");
		}
		return self::$instance;
	}

	/**
	 * A magic function to simplify accessing configuration options.
	 *
	 * @param string $propertyName
	 * @return mixed
	 */
	public function __get($propertyName)
	{
		$value = $this->getOption($propertyName);

		if (is_null($value))
		{
			try
			{
				return parent::__get($propertyName);
			}
			catch (Exception $e)
			{
				/**
				 * This is temporary for testing.  In the future we'll
				 * catch the exceptions and just return null.
				 */
//				echo "CONFIG ERROR: Unknown Property '$propertyName'\n";
//				echo "CONFIG ERROR: {$e->getMessage()} \n";
//				echo $e->getTraceAsString();
				return NULL;
			}
		}
		else
		{
			return $value;
		}
	}

	/**
	 * A magic function to simplify determining whether or not a configuration
	 * option exists.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name) {
		return $this->isOptionSet($name);
	}

	/**
	 * Returns the value of a specified configuration option.
	 *
	 * @param string $name
	 * @return mixedEnter description here...
	 */
	protected function getOption($name)
	{
		if (defined('EXECUTION_MODE') && isset ($this->configVariables[EXECUTION_MODE][$name]))
		{
			return $this->configVariables[EXECUTION_MODE][$name];
		}
		elseif (isset($this->configVariables[$name]))
		{
			return $this->configVariables[$name];
		}
		elseif (!is_null($this->baseConfig))
		{
			return $this->baseConfig->getOption($name);
		}
		return null;
	}

	/**
	 * Returns wheter or not a configuration option exists.
	 *
	 * @param string $name
	 * @return bool
	 */
	protected function isOptionSet($name)
	{
		if (isset($this->configVariables[$name]))
		{
			return true;
		}
		elseif (!is_null($this->baseConfig))
		{
			return $this->baseConfig->isOptionSet($name);
		}
		else
		{
			return false;
		}
	}

	/**
	 * This is just to simplify getting the factory, so it's
	 * ECash::getFactory() rather than
	 * ECash_Config::getInstance()->FACTORY or
	 * ECash_Config::getInstance()->getFactory()
	 * 
	 * @return ECash_Factory
	 */
	public static function getFactory()
	{
		return self::$factory;
	}

	public static function setFactory($factory)
	{
		self::$factory = $factory;
	}

	/**
	 * ease-of-use helper
	 *
	 * @return DB_Database_1
	 */
	public static function getMasterDbConnection()
	{
		try
		{
			$db = ECash::getFactory()->getDB();
			if(!empty($db))
				return $db;
			else
				return self::getDbConnection(ECash_Models_WritableModel::ALIAS_MASTER);
		}
		catch (Exception $e)
		{	
			return self::getDbConnection(ECash_Models_WritableModel::ALIAS_MASTER);
		}
	}

	/**
	 * ease-of-use helper
	 *
	 * @return DB_Database_1
	 */
	public static function getSlaveDbConnection()
	{
		return self::getDbConnection(ECash_Models_WritableModel::ALIAS_SLAVE);
	}
	
	/**
	 * Grabs a connection, sets the collation and time_zone
	 * then returns it.  [BrianR]
	 *
	 * @param db alias $db_alias
	 * @return DB_Database_1
	 */
	public static function getDbConnection($db_alias)
	{
		if(isset(self::$db_connection_pool[$db_alias]))
		{
			return self::$db_connection_pool[$db_alias];
		}
				
		$db = DB_DatabaseConfigPool_1::getConnection($db_alias);
		
		$loc_set = "SET NAMES 'latin1' COLLATE 'latin1_swedish_ci'";

		$db->exec($loc_set);

		/**
		 * We need to set the time zone when we grab the connection if we're within
		 * eCash.  OLP on the other hand should continue using the default time zone
		 * in the database to avoid problems when inserting timestamps.
		 * 
		 * The following is quite a hack but it's a sure way of checking to see if 
		 * the current instance was obstantiated via eCash or OLP since eCash will 
		 * always have the ECASH_EXEC_MODE defined as an environmental variable in
		 * the shell. [BR][#14061]
		 */
		$exec_mode = getenv('ECASH_EXEC_MODE');
		if(! empty($exec_mode))
		{
				$time_zone = ECash_Config::getInstance()->TIME_ZONE;
				$tz_set = "SET time_zone = '{$time_zone}'";
				$db->exec($tz_set);
		}

		self::$db_connection_pool[$db_alias] = $db;

		return self::$db_connection_pool[$db_alias];

	}
	
}

?>