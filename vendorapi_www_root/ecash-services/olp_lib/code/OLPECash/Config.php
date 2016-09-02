<?php
$root_path = realpath(dirname(dirname(dirname(dirname(__FILE__)))));
define('ECASH_COMMON_DIR', $root_path . '/ecash_common/');
define('ECASH_COMMON_CODE_DIR', ECASH_COMMON_DIR.'code/');
define('LIBOLUTION_DIR', $root_path . '/libolution/');
ini_set('include_path', get_include_path() . ':' . ECASH_COMMON_DIR . ':' . ECASH_COMMON_CODE_DIR);
