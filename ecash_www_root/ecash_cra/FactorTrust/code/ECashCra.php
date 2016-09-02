<?php

include_once(dirname(__FILE__).'/ECashCra/AutoLoad.php');

/**
 * ECashCra Application Class
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra
{
	/**
	 * Returns an istance of the requested driver class
	 *
	 * This function expects there to be a class by the name of 
	 * 'ECashCra_Driver_{$driver_name}'. The class may already exist, if it 
	 * doesn't it will attempt to be auto loaded.
	 * 
	 * If you do not want to use a class with the ECashCra_Driver_ prefix you 
	 * may specify your own using the option $prefix parameter.
	 * 
	 * @param string $driver_name
	 * @param string $prefix [optional]
	 * @return ECashCra_IDriver
	 */
	public static function getDriver($driver_name, $prefix = 'ECashCra_Driver_')
	{
		$class_name = $prefix.$driver_name;
		
		if (!class_exists($class_name, TRUE))
		{
			throw new InvalidArgumentException("Could not find driver for $class_name");
		}
		
		return new $class_name;
	}
}

$autoloader = new ECashCra_AutoLoad();
AutoLoad_1::addLoader($autoloader);

?>