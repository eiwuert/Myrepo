<?php 
define('TEST_DIR', dirname(__FILE__));
define('CONDOR_CODE', realpath(TEST_DIR.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'lib'));

require_once(CONDOR_CODE.DIRECTORY_SEPARATOR.'condor.class.php');
require_once CONDOR_CODE . DIRECTORY_SEPARATOR . 'PriorityServer.php';
require_once CONDOR_CODE . DIRECTORY_SEPARATOR . 'PriorityServerList.php';