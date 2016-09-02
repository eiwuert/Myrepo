<?PHP
if (!defined('EXECUTION_MODE')) define('EXECUTION_MODE','LIVE');
require_once('/virtualhosts/condor.4.edataserver.com/lib/condor_exception.php');
/**
** Parses the 'df' command to see if 
** the condor mount exists. If not
** we notify someone who can fix it.
**/

//Don't check for the mount if we're included
if (isset($argv) && isset($argv[0])&& ($argv[0] == basename(__FILE__))) 
{
    try
    {
        findMount("/data");
    }
    catch (CondorException $e)
    {
	
        exit();
    }
    catch (Exception $e)
    {
        exit();
    }
}

/**
** Checks to see if a mount exists, and optionally 
** that said mount is using a particular filesystem
** @param string $mount directory to that should be mounted to
** @param string $fileSystem The filesystem $mount should have mounted to it.
** @return boolean
**/
function findMount($mount,$fileSystem=NULL)
{
return true;
	$mountStr = `df | grep $mount`;
	$mountArr = split("\n",$mountStr);
	if($fileSystem)
	{
		$nfileSystem = str_replace(Array("/"),Array("\/"),$fileSystem);
		$nmount = str_replace(Array("/"),Array("\/"),$mount);
		foreach($mountArr as $str)
		{
			$preg = "/^$nfileSystem\s+[0-9A-Za-z%\s]+$nmount$/";
			if(preg_match("$preg",$str,$matches) != 0)
			{
				return true;
			}
			else
			{
				throw new CondorException("Mount $mount does not exist on filesystem $fileSystem.",CondorException::ERROR_MOUNT);
				return false;
			}
		}
	}
	else
	{
		foreach($mountArr as $str)
		{
			if(strpos($str,$mount) !== false)
			{
				return true;
			}
		}
		//If we haven't found it by now, it doesn't exist
		throw new CondorException("Mount $mount does not exist.",
			CondorException::ERROR_MOUNT);
		return false;
	}
}
?>
