<?php
/**
 * @package Core
 */
define('LIBOLUTION_ROOT', dirname(__FILE__));
if (! interface_exists('IAutoLoad_1', FALSE)) require_once LIBOLUTION_ROOT.'/IAutoLoad.1.php';
if (! class_exists('Object_1', FALSE)) require_once LIBOLUTION_ROOT.'/Object.1.php';

/**
 * Class included for Libolution autoloading, implements IAutoLoad
 * Libolution's all-encompassing chainable auto-loader
 * with pseudo name-spacing
 *
 * @author Justin Foell <justin.foell@sellingsource.com>
 */
class AutoLoad_1 extends Object_1 implements IAutoLoad_1
{
	/**
	 * @var array
	 */
	private static $chained_loaders = array();

	/**
	 * Adds a directory to those that are searched for classes
	 *
	 * @param string $dir
	 * @return void
	 */
	public static function addSearchPath($dir)
	{
		// resolve real paths, and get rid of any that are bad
		$paths = func_get_args();
		$paths = array_filter(array_map('realpath', $paths));

		if ($paths)
		{
			set_include_path(get_include_path().':'.implode(':', $paths));
		}
	}

	/**
	 * Add a class that implements the "load" method so it gets called
	 * automagically when a class is created.
	 *
	 * @param IAutoLoad $loader loader to be added
	 * @param int $priority (default 20) priority of loader, doesn't need to be unique
	 * @return void
	 */
	public static function addLoader(IAutoLoad_1 $loader, $priority = 20)
	{
		//if the priority exists, add multiple to the same priority
		if (isset(self::$chained_loaders[$priority]))
		{
			if (is_array(self::$chained_loaders[$priority]))
			{
				self::$chained_loaders[$priority][] = $loader;
			}
			else
			{
				//if it's just the one currently at this priority level,
				//put it and the new one into an array
				self::$chained_loaders[$priority] = array(
					self::$chained_loaders[$priority],
					$loader
				);
			}
		}
		else
		{
			self::$chained_loaders[$priority] = $loader;
		}

		//sort the array now, rather than every time in the load method
		ksort(self::$chained_loaders);
	}

	/**
	 * Called by the "magic" function __autoload
	 *
	 * @param string $class_name
	 * @return void
	 */
	public static function runLoad($class_name)
	{
		//call a private recursive method to traverse the
		self::recursiveLoad(self::$chained_loaders, $class_name);
	}

	/**
	 * Private recursive function to walk through the (possibly) two
	 * dimensional chained_loaders array.  Might be overkill for only
	 * two dimensions.
	 *
	 * @param array $loader_array array of IAutoLoad-ers
	 * @param string $class_name
	 * @return void
	 */
	private static function recursiveLoad($loader_array, $class_name)
	{
		foreach ($loader_array as $loader)
		{
			// if this priority has > 1 loader
			if (is_array($loader))
			{
				self::recursiveLoad($loader, $class_name);
			}
			elseif ($loader->load($class_name))
			{
				//this may be a hack, but if we've found our
				//class, stop looking!
				break;
			}
		}
	}

	/**
	 * Libolution's implementation of load
	 *
	 * {@source}
	 *
	 * @author Rodric Glaser
	 * @param string $class_name
	 * @return boolean returns true if file was successfully loaded
	 */
	public function load($class_name)
	{
		//turn on warnings
		$old_level = error_reporting(error_reporting() & ~E_WARNING);

		$found = include_once(self::classToPath($class_name));

		//turn off warnings
		error_reporting($old_level);

		return $found;
	}

	/**
	 * Returns the php file that the libolution autoloader will
	 * look for, given a class name
	 *
	 * @param string $class_name
	 * @return string
	 */
	public static function classToPath($class_name)
	{
		if (($pos = strrpos($class_name, '_')) !== FALSE
			&& is_numeric(substr($class_name, $pos + 1)))
		{
			$class_name{$pos} = '.';
		}
		return str_replace('_', '/', $class_name . '.php');
	}
}

/**
 * Default priority for the Libolution autoloader.
 *
 * By default, put Libolution at a higher initial priority (10, 1
 * being highest), than the addt'l chained loaders (default priority
 * 20).  This is just incase they did something stupid like throw an
 * exception. If you really want to override it's default priority,
 * define LIB_AUTO_PRIORITY before including this class.
 */
if (!defined('LIB_AUTOLOAD_PRIORITY'))
	define('LIB_AUTOLOAD_PRIORITY', 10);

AutoLoad_1::addLoader(new AutoLoad_1(), LIB_AUTOLOAD_PRIORITY);

/**
 * "magic" function __autoload
 *
 * This loader can also be used in conjunction with the SPL autoloader.
 * The SPL autoloader normally works like this:
 * <code>
 * //global function, will run 1st
 * spl_autoload_register('my_load');
 * //public static method, will run 2nd
 * spl_autoload_register(array('MyClass', 'load'));
 * //another public static notation, will run 3rd
 * spl_autoload_register('MyClass::load');
 *
 * //include the original __autoload (like the one definied in
 * //libolution).  These SPL calls effectively replace the engine
 * //cache for the __autoload function, so __autoload needs to be
 * //manually re-registered
 * spl_autoload_register('__autoload');
 *
 * //You should probably register the SPL default loader last,
 * //because if it's not found here, a Fatal error awaits you!
 * //This is a bug http://bugs.php.net/?id=39313 and
 * //should be fixed by PHP 5.2.1
 *
 * //only search for .php and .class.php extensions
 * spl_autoload_extensions(".php,.class.php");
 * //will run last, defaults to lowercase class name plus whatever
 * //extensions you defined above (defaults to ".php,.inc").
 * spl_autoload_register('spl_autoload');
 * </code>
 *
 * @param string $class_name Name of class to be loaded.
 * @link http://www.php.net/manual/en/language.oop5.autoload.php
 * @link http://www.php.net/manual/en/ref.spl.php
 * @return void
 */
function __autoload($class_name)
{
	AutoLoad_1::runLoad($class_name);
}

?>
