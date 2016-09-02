<?php
/**
 * PHPUnit bootstrap file.
 */

set_include_path(implode(PATH_SEPARATOR, array(
	realpath('../code'),
	get_include_path()
)));

require_once 'libolution/AutoLoad.1.php';
