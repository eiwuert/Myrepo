<?

/**
 * sms.config.php 
 * 
 * @desc 
 * 		SMS Web Service config
 * @version 
 * 		1.0
 * @author 
 * 		don.adriano@sellingsource.com
 * 		andrew.minerd@sellingsource.com
 * @todo 
 *
 */

// define applog constants
defined('APPLOG_SIZE_LIMIT') || DEFINE('APPLOG_SIZE_LIMIT', '1000000000');
defined('APPLOG_FILE_LIMIT') || DEFINE('APPLOG_FILE_LIMIT', '20');
defined('APPLOG_ROTATE') || DEFINE('APPLOG_ROTATE', FALSE);

if (!defined('MODE'))
{
	
	switch(TRUE)
	{
		
		// LOCAL
		// case preg_match('/(ds\d{2}.tss|gambit.tss)$/', $_SERVER['SERVER_NAME']):
		case preg_match("/\.(ds\d{2}|dev\d{2})\.tss$/i", $_SERVER['SERVER_NAME'], $matched):
		default:
			$local_name = isset($matched[1]) ? $matched[1] : 'ds57';
			defined('LOCAL_NAME') || define('LOCAL_NAME', $local_name);
			defined('MODE') || define('MODE', 'LOCAL');
			break;
			
		// RC
		case preg_match('/^rc\./', $_SERVER['SERVER_NAME']):
			defined('MODE') || define('MODE', 'RC');
			break;
			
		case preg_match('/^(sms)\./', $_SERVER['SERVER_NAME']):
			defined('MODE') || define('MODE', 'LIVE');
			break;
			
	}
	
}

switch(MODE)
{
	//LOCAL
	case 'LOCAL':
	default:
		
		// applog
		defined('APPLOG_SUBDIRECTORY') || DEFINE('APPLOG_SUBDIRECTORY', 'sms');
		defined('APPLICATION') || DEFINE('APPLICATION', 'sms');
		defined('DEBUG') || DEFINE("DEBUG", TRUE);
		
		// dir path
		defined('BASE_DIR') || DEFINE('BASE_DIR', '/virtualhosts/sms/');
		defined('INCLUDE_DIR') || DEFINE('INCLUDE_DIR', BASE_DIR . 'includes/');
		defined('WWW_DIR') || DEFINE('WWW_DIR' , BASE_DIR . 'www/');
		
		// db conf
		defined('DB_HOST') || DEFINE('DB_HOST', 'monster.tss:3309');
		defined('DB_USER') || DEFINE('DB_USER', 'sms');
		defined('DB_PASS') || DEFINE('DB_PASS', 'snailmail');
		defined('DB_NAME') || DEFINE('DB_NAME', 'sms');
		
		break;
		
		
	// RC
	case 'RC':
		// applog
		defined('APPLOG_SUBDIRECTORY') || DEFINE('APPLOG_SUBDIRECTORY', 'rc_sms');
		defined('APPLICATION') || DEFINE('APPLICATION', 'sms');
		defined('DEBUG') || DEFINE("DEBUG", TRUE);
		
		// dir path
		defined('BASE_DIR') || DEFINE('BASE_DIR', '/virtualhosts/sms/');
		defined('INCLUDE_DIR') || DEFINE('INCLUDE_DIR', BASE_DIR . 'includes/');
		defined('WWW_DIR') || DEFINE('WWW_DIR' , BASE_DIR . 'www/');
		
		// db conf
		defined('DB_HOST') || DEFINE('DB_HOST', 'db101.clkonline.com:3308');
		defined('DB_USER') || DEFINE('DB_USER', 'sms');
		defined('DB_PASS') || DEFINE('DB_PASS', 'snailmail');
		defined('DB_NAME') || DEFINE('DB_NAME', 'sms');
		
		break;
	
		
	// LIVE
	case 'LIVE':
	
		// applog
		defined('APPLOG_SUBDIRECTORY') || DEFINE('APPLOG_SUBDIRECTORY', 'sms');
		defined('APPLICATION') || DEFINE('APPLICATION', 'sms');
		
		// debug
		defined('DEBUG') || DEFINE("DEBUG", FALSE);
		
		// dir path
		defined('BASE_DIR') || DEFINE('BASE_DIR', '/virtualhosts/sms.edataserver.com/');
		defined('INCLUDE_DIR') || DEFINE('INCLUDE_DIR', BASE_DIR . 'includes/');
		defined('WWW_DIR') || DEFINE('WWW_DIR' , BASE_DIR . 'www/');
		
		// db conf
		defined('DB_HOST') || DEFINE('DB_HOST', 'db3.clkonline.com');
		defined('DB_USER') || DEFINE('DB_USER', 'sms');
		defined('DB_PASS') || DEFINE('DB_PASS', 'th1s1sn0tccs');
		defined('DB_NAME') || DEFINE('DB_NAME', 'sms');
	
		break;
}
