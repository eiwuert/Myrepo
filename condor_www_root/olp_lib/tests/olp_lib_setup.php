<?php
/**
 * Setup file for the olp_lib tests.
 * 
 * @author Brian Feaver <brian.feaver@sellingosurce.com>
 */

date_default_timezone_set('America/Los_Angeles');

// Directory paths
define('BASE_DIR', dirname(__FILE__) . '/../');

// AutoLoad (below) requires that the blackbox path be in the root
ini_set('include_path', ini_get('include_path') . ':' . BASE_DIR);

require_once('libolution/AutoLoad.1.php');
?>
