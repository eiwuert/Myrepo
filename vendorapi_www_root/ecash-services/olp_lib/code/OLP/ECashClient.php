<?php

/**
 * OLP has a couple of different eCash APIs it needs to use. Instead
 * of guessing which ones to use and how to call them, we will use this
 * class as a facade over all the other eCash APIs.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_ECashClient
{
	/**
	 * @var string
	 */
	protected $mode;
	
	/**
	 * @var string
	 */
	protected $property_short;
	
	/**
	 * An array of initialized OLP_ECashClient_IDriver classes, with the keys
	 * being the class name.
	 *
	 * @var array
	 */
	protected $drivers;
	
	/**
	 * A map of methods => API class names.
	 *
	 * @var array
	 */
	protected $method_list;
	
	/**
	 * Sets up the variables needed for this class.
	 *
	 * One ring to rule them all,
	 *
	 * @param string $mode
	 * @param string $property_short
	 */
	public function __construct($mode, $property_short)
	{
		$this->mode = $mode;
		$this->property_short = $property_short;
		
		$this->drivers = $this->loadDrivers();
		$this->method_list = $this->loadDriverMethods();
	}
	
	/**
	 * Loads up all the OLP_ECashClient_IDriver.
	 *
	 * One ring to find them,
	 *
	 * @return array
	 */
	protected function loadDrivers()
	{
		$drivers = array();
		
		$class_names = $this->loadPossibleClassNames();
		
		if (is_array($class_names))
		{
			foreach ($class_names AS $class_name)
			{
				// Verify that we can load it
				if (class_exists($class_name))
				{
					$reflect_class = new ReflectionClass($class_name);
					
					// Verify that we can initialize it and implements our interface
					if ($reflect_class->isInstantiable()
						&& $reflect_class->implementsInterface('OLP_ECashClient_IDriver')
					)
					{
						$drivers[$class_name] = new $class_name(
							$this->mode,
							$this->property_short
						);
					}
				}
			}
		}
		
		return $drivers;
	}
	
	/**
	 * Return a list of class names to test.
	 *
	 * One ring to bring them all,
	 *
	 * @param array
	 */
	protected function loadPossibleClassNames()
	{
		$lib_base = realpath(dirname(dirname(__FILE__))) . '/';
		$driver_base = str_replace('_', '/', get_class($this));
		$class_names = array();
		
		$search = array(
			$lib_base,
			'.php',
			'/',
		);
		$replace = array(
			'',
			'',
			'_',
		);
		
		// Find all classes in the subfolder API
		$files_to_check = glob("{$lib_base}{$driver_base}/*.php");
		foreach ($files_to_check AS $filename)
		{
			$class_names[] = str_replace($search, $replace, $filename);
		}
		
		return $class_names;
	}
	
	/**
	 * Loads up all the methods for each driver.
	 *
	 * And in the darkness bind them.
	 *
	 * @return void
	 */
	protected function loadDriverMethods()
	{
		$method_list = array();
		
		if (is_array($this->drivers))
		{
			foreach ($this->drivers AS $class_name => $class)
			{
				$class_method_list = $class->getMethodList();
				
				if (is_array($class_method_list))
				{
					$method_list = array_merge(
						$method_list,
						array_fill_keys(
							$class_method_list,
							$class_name
						)
					);
				}
			}
		}
		
		return $method_list;
	}
	
	/**
	 * The magic of the class, determine which driver to call.
	 *
	 * In the end, there can be only one.
	 *
	 * @param string $method_name
	 * @param array $parameters
	 * @return mixed
	 */
	public function __call($method_name, array $parameters)
	{
		if (!isset($this->method_list[$method_name]))
		{
			throw new Exception(sprintf("Unknown %s method %s.",
				get_class($this),
				$method_name
			));
		}
		
		$driver_class_name = $this->method_list[$method_name];
		$driver = $this->drivers[$driver_class_name];
		
		$data = call_user_func_array(
			array($this->drivers[$this->method_list[$method_name]], $method_name),
			$parameters
		);
		
		return $data;
	}
	
	/**
	 * Returns a list of all methods for all drivers.
	 *
	 * May it be Duncan MacLeod, the Highlander.
	 *
	 * @return array
	 */
	public function getVerboseMethodList()
	{
		$verbose_method_list = array();
		
		foreach ($this->drivers AS $class_name => $driver)
		{
			$verbose_method_list[$class_name] = array(
				'name' => $class_name,
				'description' => $driver->getDriverDescription(),
				'methods' => $driver->getVerboseMethodList(),
			);
		}
		
		return $verbose_method_list;
	}
}

?>
