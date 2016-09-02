<?php

require_once(DIR_LIB . "session_pool.1.php");

//settings
$lifespan = "-7 days";
//end settings


$share_list = array();
//get the directories
if ( is_dir(SESSION_DIR) ) 
{
	if ( $share_list = scandir(SESSION_DIR) ) 
	{
		array_shift($share_list); array_shift($share_list); // get rid of . and ..
	}
}

foreach($share_list as $dir)
{
	$files = scandir(SESSION_DIR . $dir);
	array_shift($files); array_shift($files); // get rid of . and ..	
	foreach($files as $file)
	{
		$date = filemtime(SESSION_DIR . $dir . '/' . $file);
		$exp_date = strtotime($lifespan);
		if($date < $exp_date)
		{
			//echo "unlink: " . SESSION_DIR . $dir . '/' . $file . "\n";
			unlink(SESSION_DIR . $dir . '/' . $file);
		}
	}
}

?>