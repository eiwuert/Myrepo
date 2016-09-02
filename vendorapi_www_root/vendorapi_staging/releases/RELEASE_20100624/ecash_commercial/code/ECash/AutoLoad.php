<?php

require_once('libolution/AutoLoad.1.php');

/**
 * ECash_AutoLoad
 *
 * Revision History:
 *      2007-11-28 - rlee - Modified to suit just new eCash libs
 * 		2008-02-11 - jbelich - Modified to suit older style ecash libs
 *      2008-02-28 - rlee - Modified to simplify name matching
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @author Russell Lee <russell.lee@sellingsource.com>
 */
class ECash_AutoLoad implements IAutoLoad_1
{
	/**
	 * Attempts to load a class within the eCash lib structure based on its name.
	 *
	 * First attempts to load subdir pattern names that begin with ECash_.
	 * Then defaults to previous autoload functionality: checks each code and lib directory directly.
	 *
	 * @param string $class_name
	 * @return boolean
	 */
	public function load($class_name)
	{
		if (class_exists($class_name, false) === TRUE)
		{
			return TRUE;
		}

		$base_class_name = $class_name;
		if (stripos($base_class_name, 'ecashui') === 0
			|| stripos($base_class_name, 'ecash') === 0)
		{
			$base_dir = dirname(__FILE__) . '/..';
		}
		elseif (stripos($base_class_name, 'sfService') === 0)
		{
			$base_dir = dirname(__FILE__) . '/../../external_libraries/symfony_di/lib';
		}
		
		if (stripos($base_class_name, 'custom_') === 0)
		{
			$base_dir = CUSTOMER_LIB;
			$base_class_name = substr($base_class_name, 7);
		}

		if (isset($base_dir))
		{
			$class_file_base = str_replace('_', '/', $base_class_name);

			if (file_exists($base_dir . '/' . $class_file_base . '.php'))
			{
				include_once($base_dir . '/' . $class_file_base . '.php');
				return TRUE;
			}
			else if (file_exists($base_dir . $class_file_base . '.class.php'))
			{
				include_once($base_dir . $class_file_base . '.class.php');
				return TRUE;
			}
			else if (file_exists($base_dir . $class_file_base . '/' . basename($class_file_base) . '.class.php'))
			{
				include_once($base_dir . $class_file_base . '/' . basename($class_file_base) . '.class.php');
				return TRUE;
			}
		}

		// Backwards compatible with the previous 'common_functions' __autoload function.

		$partial_path = strtolower($class_name) . '.class.php';

		if (file_exists(CLIENT_CODE_DIR . $partial_path))
		{
			include_once(CLIENT_CODE_DIR . $partial_path);
			return TRUE;
		}
		elseif (file_exists(SERVER_CODE_DIR . $partial_path))
		{
			include_once(SERVER_CODE_DIR . $partial_path);
			return TRUE;
		}
		elseif (file_exists(LIB_DIR . $partial_path))
		{
			include_once(LIB_DIR . $partial_path);
			return TRUE;
		}

		return FALSE;
	}
}

AutoLoad_1::addLoader(new ECash_AutoLoad(), 5);

?>
