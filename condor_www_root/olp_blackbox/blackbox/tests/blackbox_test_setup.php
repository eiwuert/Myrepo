<?php
/**
 * Config file for Blackbox PHPUnit tests.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

date_default_timezone_set('America/Los_Angeles');

// Directory paths
define('BASE_DIR', dirname(__FILE__) . '/../');

// AutoLoad (below) requires that the blackbox path be in the root
ini_set('include_path', ini_get('include_path') . ':' . BASE_DIR);

require_once('libolution/AutoLoad.1.php');

/**
 * Temporary (I assume) object that lets you provide a data array to Blackbox_Data
 * 
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class Blackbox_DataTestObj extends Blackbox_Data
{
	/**
	 * Blackbox_DataTestObj constructor
	 * 
	 * @param array $data the data for the object
	 */
	public function __construct(array $data)
	{
		$this->data = $data;
	}
}
?>
