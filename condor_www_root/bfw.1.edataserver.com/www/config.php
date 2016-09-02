<?php
define('BFW_WWW_DIR',dirname(__FILE__).'/');
require_once(BFW_WWW_DIR . '../include/code/module_test.php');
require_once('mode_test.php');
require_once('maintenance_mode.php');
require_once('libolution/AutoLoad.1.php');
require_once(BFW_WWW_DIR . '../include/code/crypt_config.php');

// define applog constants
if(!defined('APPLOG_SIZE_LIMIT')) define('APPLOG_SIZE_LIMIT', '1000000000');
if(!defined('APPLOG_FILE_LIMIT')) define('APPLOG_FILE_LIMIT', '20');
if(!defined('APPLOG_ROTATE')) define('APPLOG_ROTATE', FALSE);
if(!defined('APPLOG_OLE_SUBDIRECTORY')) define('APPLOG_OLE_SUBDIRECTORY','ole');
if(!defined('APPLOG_UMASK')) define('APPLOG_UMASK',002);
DEFINE('MYSQL4_LOG',false);
DEFINE('STATPRO_LOG',false);

define('MAX_SESSION_SIZE', 50000);	// Maximum session size - currenty 50 KB
define('SESSION_SIZE_LIMIT', true);

//Get Module
$module = Module_Test::Get_Module();
//Get Mode
$mode = strtoupper(Mode_Test::Get_Mode_As_String());



//Force to live if not set
if($mode == "UNKNOWN") $mode = "LIVE";
if($mode == "NW")
{
	$mode = "RC";
	DEFINE('BFW_MODE_NW',TRUE);
}
else
{
	DEFINE('BFW_MODE_NW',FALSE);
}
DEFINE('BFW_MODE',$mode);
DEFINE('BFW_LOCAL_NAME',Mode_Test::Get_Local_Machine_Name());

// BFW_MODE needs to be defined before the memcache servers are included
require_once 'memcache_servers.php';

switch($module)
{
	// OLP
	case Module_Test::$OLP:
	default:
		DEFINE('APPLICATION', 'olp'); //For Applog
		DEFINE('APPLOG_SUBDIRECTORY', 'olp');
		if(!defined('APPLICATION')) DEFINE('APPLICATION', 'olp');
		DEFINE('DIR_LIB', '/virtualhosts/lib');
		DEFINE('DIR_LIB5', '/virtualhosts/lib5');
		if(BFW_MODE_NW)
		{
			DEFINE('BFW_BASE_DIR', '/virtualhosts/nw.bfw.1.edataserver.com/');
		}
		else
		{
			$base = dirname(__FILE__) . "/../";
			DEFINE('BFW_BASE_DIR', $base);
		}		
		DEFINE('BFW_MODULE_DIR', BFW_BASE_DIR . 'include/modules/');
		DEFINE('BFW_CODE_DIR' , BFW_BASE_DIR . 'include/code/');
        DEFINE('BFW_USE_MODULE', 'olp');
		break;
		
    // OCP
	case Module_Test::$OCP:
		DEFINE('APPLICATION', 'ocp'); //For Applog
				DEFINE('APPLOG_SUBDIRECTORY', 'ocp');
				if(!defined('APPLICATION')) DEFINE('APPLICATION', 'ocp');
				DEFINE('DIR_LIB', '/virtualhosts/lib');
				DEFINE('DIR_LIB5', '/virtualhosts/lib5');
				if(BFW_MODE_NW)
				{
					DEFINE('BFW_BASE_DIR', '/virtualhosts/nw.bfw.1.edataserver.com/');
				}
				else
				{
					DEFINE('BFW_BASE_DIR', '/virtualhosts/bfw.1.edataserver.com/');
				}        
				DEFINE('BFW_MODULE_DIR', BFW_BASE_DIR . 'include/modules/');
				DEFINE('BFW_CODE_DIR' , BFW_BASE_DIR . 'include/code/');
				DEFINE('BFW_USE_MODULE', 'ocp');
				break;
		
	// Card Services
	case Module_Test::$CCS:
		switch(BFW_MODE)
		{
			//LOCAL
			case "LOCAL":
			default:
				DEFINE('APPLOG_SUBDIRECTORY', 'ccs');
				if(!defined('APPLICATION')) DEFINE('APPLICATION', 'ccs');
				DEFINE('DEBUG', FALSE);
				DEFINE('DIR_LIB', '/virtualhosts/lib/');
				DEFINE('DIR_LIB5', '/virtualhosts/lib5/');
				DEFINE('BFW_BASE_DIR', '/virtualhosts/ccs.1.edataserver.com/');		
				DEFINE('BFW_MODULE_DIR', BFW_BASE_DIR . 'include/modules/');
				DEFINE('BFW_CODE_DIR' , BFW_BASE_DIR . 'include/code/');
                DEFINE('BFW_USE_MODULE', 'ccs');
				break;
				
			// RC
			case "RC":
				DEFINE('APPLOG_SUBDIRECTORY', 'rc_ccs');
				if(!defined('APPLICATION')) DEFINE('APPLICATION', 'rc_ccs');
				DEFINE('DEBUG', FALSE);
				DEFINE('DIR_LIB', '/virtualhosts/rc_lib/');
				DEFINE('DIR_LIB5', '/virtualhosts/rc_lib5/');
				DEFINE('BFW_BASE_DIR', '/virtualhosts/edataserver.com/ccs.1/rc/');		
				DEFINE('BFW_MODULE_DIR', BFW_BASE_DIR . 'include/modules/');
				DEFINE('BFW_CODE_DIR' , BFW_BASE_DIR . 'include/code/');
                DEFINE('BFW_USE_MODULE', 'ccs');
				break;
			
			// live
			case "LIVE":
				DEFINE('APPLOG_SUBDIRECTORY', 'ccs');
				if(!defined('APPLICATION')) DEFINE('APPLICATION', 'ccs');
				DEFINE('DEBUG', FALSE);
				DEFINE('DIR_LIB', '/virtualhosts/lib/');
				DEFINE('DIR_LIB5', '/virtualhosts/lib5/');
				DEFINE('BFW_BASE_DIR', '/virtualhosts/edataserver.com/ccs.1/live/');		
				DEFINE('BFW_MODULE_DIR', BFW_BASE_DIR . 'include/modules/');
				DEFINE('BFW_CODE_DIR' , BFW_BASE_DIR . 'include/code/');
                DEFINE('BFW_USE_MODULE', 'ccs');
				break;
		}
}

$crypt_config = Crypt_Config::Get_Config(BFW_MODE);
if(!defined('ENC_KEY')) define('ENC_KEY',$crypt_config["KEY"]);
if(!defined('ENC_IV')) define('ENC_IV',$crypt_config["IV"]);

require_once BFW_CODE_DIR . 'failover_config.php';

//RUN The Failover Config if we're not in maintenance mode
$maintenance_mode = new Maintenance_Mode();
if($maintenance_mode->Is_Online())
{
	Failover_Config::RunConfig();
}
?>
