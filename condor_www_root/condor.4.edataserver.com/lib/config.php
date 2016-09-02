<?php
/**
 * Condor Configuration
 * 
 * This file defines configuration information for condor
 * 
 * @author Jason Gabriele <jason.gabriele@sellingsource.com>
 * 
 * @version
 * 	    1.0.0 Feb 20, 2007 - Jason Gabriele <jason.gabriele@sellingsource.com>
 */ 
require_once('mysql_pool.php');
require_once('cache.php');
require_once('condor_applog.php');

error_reporting(E_ALL);

//This just determines which
//messages to actually log out
define('DEBUG_LEVEL',1000);

if (!defined('MODE_LIVE')) define('MODE_LIVE', 'LIVE');
if (!defined('MODE_RC')) define('MODE_RC', 'RC');
if (!defined('MODE_DEV')) define('MODE_DEV', 'LOCAL');
if (!defined('MODE_DEMO')) define('MODE_DEMO', 'LOCAL');

define('BIN_TIFF2PDF','tiff2pdf');
define('CONDOR_DIR',realpath(dirname(__FILE__).'/../').'/');
define('SEND_MISSING_TOKEN_ALERT',true);

//MySQL_Pool::Define('condor_' . MODE_DEV, 'monster.tss', 'condor', 'password', 'condor', 3320);
MySQL_Pool::Define('condor_' . MODE_DEV,  'localhost', 'root','','condor',3306);
MySQL_Pool::Define('condor_' . MODE_RC,   'db101.ept.tss', 'condor', 'andean', 'condor', 3313);
MySQL_Pool::Define('condor_' . MODE_LIVE, 'writer.mysql.loanservicingcompany.com', 'condor', 'password', 'condor', 3306);
MySQL_Pool::Define('condor_' . MODE_DEMO, 'db101.ept.tss', 'condor', 'andean', 'condor_demo', 3313);



//File storage directory
define('CONDOR_ROOT_DIR', '/data');
//define('CONDOR_ROOT_DIR', '/var/lib/condor');
//If we lose the NFS mount, use this directory
define('CONDOR_BACKUP_DIR', '/condor_backup');
//The Copia Import Directory
define('COPIA_FILE', '/data/copia_import/incoming/%04u/%08u.TIF.gz');

//Remote file service configs
define('CONDOR_REMOTE_SERVER', 'condor-drive');
define('CONDOR_REMOTE_PORT', 22);
define('CONDOR_REMOTE_USER', 'condor');
define('CONDOR_REMOTE_CRED', 'c0nd0r');

Cache::DefineServer(MODE_LIVE,'condor-drive');
Cache::DefineServer(MODE_DEV,'localhost');
Cache::DefineServer(MODE_RC,'localhost');

?>
