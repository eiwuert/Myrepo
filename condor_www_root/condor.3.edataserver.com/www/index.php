<?PHP
// Condor Constants
DEFINE("DIR_CODE","../include/code/");

// Include the config file
require_once(DIR_CODE."condor.global.cfg.php");

// Instantiate condor
if(!$_REQUEST['page'])
{
	new Condor();
}
else 
{
	require_once(DIR_CODE.'condor.admin.class.php');
	new Condor_Admin($sql);
}
?>