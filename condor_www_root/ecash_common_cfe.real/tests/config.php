<?php

define('BASE_DIR', realpath('../../ecash').'/');
define('LIB_DIR', BASE_DIR.'lib/');
define('ECASH_COMMON_DIR', realpath('../').'/');
define('COMMON_LIB_DIR', '/virtualhosts/lib/');

require 'libolution/AutoLoad.1.php';
AutoLoad_1::addLoader(new AutoLoad_1('../code/'));

class_exists('DB_Database_1', true);

?>
