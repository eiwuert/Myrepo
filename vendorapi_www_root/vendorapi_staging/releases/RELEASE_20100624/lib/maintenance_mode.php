<?php
/**
 * Maintenace Mode
 * 
 * This class can be called to test for maintenance mode
 * before trying to submit to the backend
 * 
 * @author Jason Gabriele <jason.gabriele@sellingsource.com>
 * 
 * @example
 * 	$maintenance_mode = new Maintenance_Mode();
 *  if (!$maintenance_mode->Is_Online()) 		
 *     //set page to maintenance mode, etc
 * 
 * @version
 * 	    1.0.0 May 12, 2006 - Jason Gabriele <jason.gabriele@sellingsource.com>
 */
if(isset($FE_ENVIRONMENT) &&
   isset($FE_ENVIRONMENT["SHARED_CODE_SMT"]) && 
   file_exists($FE_ENVIRONMENT["SHARED_CODE_SMT"] . '/maintenance_status.php'))
{
    require_once $FE_ENVIRONMENT["SHARED_CODE_SMT"] . '/maintenance_status.php';
}
elseif(file_exists('/virtualhosts/bfw.1.edataserver.com/www/maintenance_status.php'))
{
	require_once '/virtualhosts/bfw.1.edataserver.com/www/maintenance_status.php';
}
elseif(file_exists('/virtualhosts/lib/maintenance_status.php'))
{
	require_once '/virtualhosts/lib/maintenance_status.php';
}

//Set it on if for some reason it isnt loaded
if(!defined('MASTER_SITE_ONLINE'))
{
	define('MASTER_SITE_ONLINE',true);
}

class Maintenance_Mode
{
    function Is_Online()
    {
    	if(MASTER_SITE_ONLINE)
        {
			if(defined('MAINTENANCE_MODE') && MAINTENANCE_MODE === true)
			{
				return false;
			}
			else
			{
        		return true;
			}
        }
        else 
        {
            return false;
        }
    }
}
?>
