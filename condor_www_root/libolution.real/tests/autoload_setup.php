<?php

define('LIBOLUTION_DIR', realpath(dirname(__FILE__).'/../').'/');
define('LIBOLUTION_ROOT', realpath(LIBOLUTION_DIR.'/../').'/');

set_include_path('./libolution:.:/usr/share/php');
require_once '../AutoLoad.1.php';

/**
 * Because DB_IStatement_1 extends Traversable, which requires that
 * any implementors also implement either Iterator or IteratorAggregate,
 * we can't simply mock DB_IStatement_1.
 */
abstract class StatementMock implements IteratorAggregate, DB_IStatement_1 {}

/**
 * A class to do some magic
 * We can add this to the static autoloader's list,
 * but then change the actual loader getting used. This
 * allow us to unit test multiple times.
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class AutoLoadMock implements IAutoLoad_1
{
	/**
	 * @var IAutoLoad_1
	 */
	protected $loader;

	/**
	 * Sets the loader that will be used
	 * @param IAutoLoad_1 $load
	 * @return void
	 */
	public function setLoader(IAutoLoad_1 $load)
	{
		$this->loader = $load;
	}

	/**
	 * Clears the loader
	 * @return void
	 */
	public function clear()
	{
		$this->loader = NULL;
	}

	/**
	 * Loads a class
	 * @param string $name
	 * @return void
	 */
	public function load($name)
	{
		if ($this->loader)
			return $this->loader->load($name);
		return FALSE;
	}
}

?>
