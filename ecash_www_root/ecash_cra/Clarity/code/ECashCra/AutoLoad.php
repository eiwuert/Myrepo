<?php

include_once('libolution/AutoLoad.1.php');

/**
 * The ecash cra auto loader
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_AutoLoad extends AutoLoad_1 
{
	/**
	 * ECashCra Autoloader
	 *
	 * @param string $class_name
	 * @return boolean returns true if file was successfully loaded
	 */
	public function load($class_name)
	{
		$path = str_replace('_', '/', $class_name).'.php';
		$filename = $this->getBaseDir().$path;
		if(file_exists($filename))
		{
			return include_once($filename);
		}

		return FALSE;
	}
	
	/**
	 * Returns the base directory for ecash cra code.
	 *
	 * @return string
	 */
	protected function getBaseDir()
	{
		return dirname(__FILE__).'/../';
	}
}

?>
