<?php
/**
 * Contains the site config for a website.
 * 
 * Typical usage for this class would be SiteConfig::getInstance()->variable_name. You can assign
 * values to it without using get or setter methods.
 * 
 * You can only set a variable in the config once. Once it's set, you can't modify it without
 * unsetting it first. This prevents you from accidently overwriting a config option. If you want
 * to change it, you need to know enough to unset it before you set it again. Keep in mind though
 * that once it's changed, it's not going to be saved anywhere and on the next run through, it will
 * be overwritten.
 *
 * @author Brian Feaver
 */
class SiteConfig
{
	/**
	 * Config data.
	 *
	 * @var array
	 */
	private $store = array();
	
	/**
	 * An instance of the SiteConfig.
	 *
	 * @var SiteConfig
	 */
	static private $instance = NULL;
	
	/**
	 * SiteConfig constructor.
	 *
	 */
	private function __construct($config = NULL)
	{
		$this->store = $config;
	}
	
	/**
	 * This would normally clone the class. We aren't allowing that.
	 *
	 */
	private function __clone() {}
	
	/**
	 * Returns the instance of the Config_Registry.
	 *
	 * @return Config_Registry
	 */
	public static function getInstance()
	{
		if(self::$instance == NULL)
		{
			self::$instance = new SiteConfig();
		}
		
		return self::$instance;
	}
	
	/**
	 * Registry set method. You can not reset a registry variable once it has been set without
	 * first unset()'ing it.
	 *
	 * @param string $label
	 * @param mixed $object
	 */
	public function __set($label, $object)
	{
		if(!isset($this->store[$label]))
		{
			$this->store[$label] = $object;
		}
	}
	
	/**
	 * Used to unset a variable in the registry. This must be called before a value can be changed.
	 *
	 * @param string $label
	 */
	public function __unset($label)
	{
		if(isset($this->store[$label]))
		{
			unset($this->store[$label]);
		}
	}
	
	/**
	 * Returns a registry variable.
	 *
	 * @param string $label
	 * @return mixed|bool
	 */
	public function __get($label)
	{
		if(isset($this->store[$label]))
		{
			return $this->store[$label];
		}
		
		return FALSE;
	}
	
	/**
	 * Checks to see if a registry variable is registered.
	 *
	 * @param string $label
	 * @return bool
	 */
	public function __isset($label)
	{
		if(isset($this->store[$label]))
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Returns the config as an object.
	 * 
	 * This is a temporary function to allow us to continue to store the config in the $_SESSION
	 * variable. In other words, this allows us to cheat.
	 *
	 * @return object
	 */
	public function asObject()
	{
		$config = new stdClass();
		
		foreach($this->store as $name => $value)
		{
			$config->$name = $value;
		}
		
		return $config;
	}
	
	/**
	 * Sets the config from an existing object.
	 * 
	 * This is a temporary function to allow us to continue to store the config in the $_SESSION
	 * variable. In other words, this allows us to cheat.
	 *
	 * @param object $config
	 * @return SiteConfig
	 */
	public static function fromObject($config)
	{
		foreach($config as $name => $value)
		{
			$store[$name] = $value;
		}
		self::$instance = new SiteConfig($store);
		return self::$instance;
	}
}
?>
