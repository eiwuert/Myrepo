<?php

$cli_environment = FALSE;

if ( !isset($_SERVER['SERVER_NAME']) || strlen($_SERVER['SERVER_NAME']) == 0 )
{
	if ( isset($_BATCH_XEQ_MODE) && strlen($_BATCH_XEQ_MODE) > 0 )
	{
		$mode = $_BATCH_XEQ_MODE;
		$cli_environment = TRUE;
	}
	else
	{
		// Failsafe
		die ("ERROR: could not determine execution mode!");
	}
}
else
{
	require_once("/virtualhosts/lib/automode.1.php"); // Always use LIVE Auto_Mode class
	$auto_mode = new Auto_Mode();
	$mode = $auto_mode->Fetch_Mode($_SERVER['SERVER_NAME']);
}

$mode = strtoupper($mode);

switch ($mode)
{
	case "RC": // The rc server, for cron/command line testing use
		define ("EXECUTION_MODE", 'RC');
		define ("DB_HOST", 'db101.clkonline.com');
		define ("DB_NAME", 'condor_admin');
		define ("CONDOR_DB_NAME", 'condor');
		define ("DB_USER", 'condor');
		define ("DB_PASS", 'andean');
		define ("DB_PORT", '3313');
		define ("STAT_MYSQL_HOST", 'db101.ept.tss');
		define ("STAT_MYSQL_USER", 'condor');
		define ("STAT_MYSQL_PASS", 'andean');
		define ("BASE_DIR", '/virtualhosts/condor_admin.edataserver.com/');
		define ('DIR_LIB', '/virtualhosts/lib/');            // lib directory for ccsadmin
		define ("COMMON_LIB_DIR", '/virtualhosts/lib/');
		define ("COMMON_LIB_ALT_DIR", '/virtualhosts/lib5/');
		//define ("URL_NMS", 'rc.ccsadmin.edataserver.com');
		define ("URL_PAYDATE_WIDGET", "http://rc.widget.1.edataserver.com");
		define ("APPLOG_SUBDIRECTORY", 'condor_admin');
		define ('CLI_EXE_PATH', '/usr/local/bin/');
		define ("REACT_URL",'http://rc.ecashapp.com/');

	break;

	case "LOCAL": // The ds locations
		define ("EXECUTION_MODE", 'LOCAL');
		define ("DB_HOST", 'localhost');
		define ("DB_NAME", 'condor_admin');
		define ("CONDOR_DB_NAME", 'condor');
		define ("DB_USER", 'root');
		define ("DB_PASS", 'toor');
		define ("DB_PORT", '3306');
		define ("STAT_MYSQL_HOST", 'beast');
		define ("STAT_MYSQL_USER", 'root');
		define ("STAT_MYSQL_PASS", '');
		define ("BASE_DIR", str_replace("www", "", getcwd()));
		define ('DIR_LIB', '/virtualhosts/lib/');            // lib directory for ccsadmin
		define ('CONDOR_API_DIR', '/virtualhosts/condor.4.edataserver.com/');
		define ("COMMON_LIB_DIR", '/virtualhosts/lib/');
		define ("COMMON_LIB_ALT_DIR", '/virtualhosts/lib5/');
		if (!$cli_environment)
		{
			$local_name = $auto_mode->Get_Local_Name();
			define ("URL_NMS", "ccsadmin.{$local_name}.tss");
			define ("URL_PAYDATE_WIDGET", "http://widget.1.paydate.{$local_name}.tss/");
		}		
		define ("APPLOG_SUBDIRECTORY", 'condor_admin');
		define ('CLI_EXE_PATH', '/opt/php5/bin/');
		define ("REACT_URL",'http://rc.ecashapp.com/');

	break;

	case "LIVE":
	default: // It must be live
		define ("EXECUTION_MODE", 'LIVE');
		define ("DB_HOST", 'writer.mysql.loanservicingcompany.com');
		define ("DB_NAME", 'condor_admin');
		define ("CONDOR_DB_NAME", 'condor');
		define ("DB_USER", 'condor');
		define ("DB_PASS", 'fats13_toast');
		define ("DB_PORT", 3306);
		define ("STAT_MYSQL_HOST", 'writer.condor2.ept.tss');
		define ("STAT_MYSQL_USER", 'condor');
		define ("STAT_MYSQL_PASS", 'flyaway');
		define ("BASE_DIR", '/virtualhosts/condor_admin.edataserver.com/');
		define ('DIR_LIB', '/virtualhosts/lib/');            // lib directory for ccsadmin
		define ("COMMON_LIB_DIR", '/virtualhosts/lib/');
		define ("COMMON_LIB_ALT_DIR", '/virtualhosts/lib5/');
		define ("URL_NMS", 'ccsadmin');
		define ("URL_PAYDATE_WIDGET", "http://paydatewizard.nationalmoneyonline.com");
		define ("APPLOG_SUBDIRECTORY", 'condor_admin');
		define ('CLI_EXE_PATH', '/usr/local/bin/');
		define ("REACT_URL",'http://ecashapp.com/');

	break;
}

define ("SYSTEM_NAME_SHORT", "ccsadmin"); // This should be set to the name_short used in the system reference table.
define ("CLIENT_CODE_DIR", BASE_DIR . 'client/code/');
define ("CLIENT_VIEW_DIR", BASE_DIR . 'client/view/');
define ("CLIENT_MODULE_DIR", BASE_DIR . 'client/module/');
define ("SERVER_CODE_DIR", BASE_DIR . 'server/code/');
define ("SERVER_MODULE_DIR", BASE_DIR . 'server/module/');
define ("WWW_DIR", BASE_DIR . 'www/');
define ("LIB_DIR", BASE_DIR . 'lib/');
define ('CLI_SCRIPT_PATH', SERVER_CODE_DIR);
define ("EDITOR_CODE_DIR", WWW_DIR . 'CuteEditor_Files');

ini_set('include_path', COMMON_LIB_ALT_DIR. ':' . DIR_LIB . ':' . COMMON_LIB_DIR.':'.LIB_DIR.':'.ini_get('include_path'));

define ("MAX_SEARCH_DISPLAY_ROWS", 500);
define ("MAX_REPORT_DISPLAY_ROWS", 10000);
define ("APPLOG_SIZE_LIMIT", 5000000);
define ("APPLOG_FILE_LIMIT", 20);
define ("SESSION_EXPIRATION_HOURS",12);
define ("SCRIPT_TIME_LIMIT_SECONDS", 60);
define ("PHP_MEMORY_USE_THRESHOLD", 50000000);
defined("DEFAULT_SITE_LOCATION") || define ("DEFAULT_SITE_LOCATION", "/" );
define ("TIME_ZONE", "'US/Pacific'");
define ("GOD_USER", "tss");

define ("DISPLAY_DATETIME_FORMAT", "m/d/Y h:i:s A");
define ("DISPLAY_DATE_FORMAT", "m/d/Y");



function Set_Company_Constants($company_abbrev)
{
	//the APPLOG_CONTEXT defines were causing notices please FIXME
	
	$company_abbrev = strtolower($company_abbrev);

	// I think the REACT_URL is supposed to be company specific but I don't know how to use this section of code yet.
	// define ("REACT_URL",'http://rc.ecashapp.com/');
	
	switch($company_abbrev)
	{
		case 'fcp3':
			// This is the Fact Cash Preferred card (had to name it fcp2 instead of fcp because fcp was already taken in site type).
			break;

		case 'fcmc':
			break;

		case 'egc':
			break;
			
		case 'ecl':
			break;
			
		case 'gl':
			break;

	}
}

?>
