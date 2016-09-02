<?php

require_once LIBOLUTION_DIR . 'Object.1.php';
require_once LIBOLUTION_DIR . 'DB/MySQLConfig.1.php';
require_once LIBOLUTION_DIR . 'DB/DatabaseConfigPool.1.php';
require_once 'Models/WritableModel.php';
require_once 'Factory.php';


/**
 * A Base class to manage multiple ecash configurations.
 *
 *
 * Example usage:
 *
 * ECash::setConfig(new CLKConfig(new LiveEnvironment()));
 *
 * -- execute a whole bunch of code --
 * $companyName = ECash::getConfig()->company_name;
 * $executionMode = ECash::getConfig()->execution_mode;
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
	const DB_MASTER_ID = 'DB_MASTER_CONFIG';
	const DB_SLAVE_ID = 'DB_SLAVE_CONFIG';
	const DB_APPSERVICE_ID = 'DB_APPSERVICE_CONFIG';
	const DB_STATEOBJECT_ID = 'DB_STATEOBJECT_CONFIG';

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
	 * Create a new ecash config object. This should never be called directly.
	 * Use getInstance() instead
	 *
	 * @param ECash_Config $baseConfig
	 * @see ECash::getConfig()
	 */
	public function __construct(ECash_Config $baseConfig = null)
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

	/**
	 * A function to return the base config or this object if it is the base.
	 *
	 * 
	 * @return ECash_Config
	 */
	public function getBaseConfig()
	{
		if(!empty($this->baseConfig))
		{
			return $this->baseConfig;
		}
		else
		{
			return $this;
		}		
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
				// @todo We should not be swallowing these exceptions.
				// We need to implement logging to find instances where
				// configs are not defined and then we need to define them 
				// and get rid of this catch. It can only cause issues down
				// the road.
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
		//@todo This has got to go. The whole point of the config object is to provide decoration for
		// environment overrides...and environments === execution_mode
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
	 * Returns a database configuration object based on the given id.
	 *
	 * @param string $var
	 * @return DB_IDatabaseConfig_1
	 */
	public function getDbConfig($var)
	{
		if (!$this->isOptionSet($var) || (!$this->getOption($var) instanceof DB_IDatabaseConfig_1))
		{
			throw new InvalidArgumentException("The specified db config '{$var}' does not exist.");
		}
		
		return $this->getOption($var);
	}
}

?>
