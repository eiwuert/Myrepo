<?php
/**
 * Libolution Autoloader Interface
 *
 * Interface with the "load" method which can be implemented and then
 * used for stringing or "chaining" several autoloaders together for
 * use in the same project.
 *
 * @author Justin Foell <justin.foell@sellingsource.com>
 * @package Core
 *
 */

/**
 * Interface with the "load" method
 *
 * @package Core
 */
interface IAutoLoad_1
{
	/**
	 * @param string $class_name Name of class to be loaded
	 * @return boolean Should return true if class was successfully loaded
	 */
	public function load($class_name);
}

?>
