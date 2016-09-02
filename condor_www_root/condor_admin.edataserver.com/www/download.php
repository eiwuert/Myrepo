<?php
require_once('config.php');
require_once(LIB_DIR . 'common_functions.php');
// Dirty way of setting up Downloading A Template File
try
{
	require(BASE_DIR.'/server/code/server.class.php');
	
	$session_id = ($_REQUEST['ssid']) ? $_REQUEST['ssid'] : null;
	$server = new Server($session_id);
	require_once(BASE_DIR.'/server/code/condor_template_query.class.php');
	$template_query = new Condor_Template_Query($server);
	if(isset($_GET['template_id']) && is_numeric($_GET['template_id']))
	{
		$template = $template_query->Fetch_Single($_GET['template_id']);
		if(is_object($template))
		{
			$fname = str_replace(' ','_',$template->name).".rtf";
			header("Content-Type: {$template->content_type}");
			header("Content-Disposition: attachment; filename=\"$fname\";");
			echo $template->data;
		}
		else 
		{
			echo("There is no template with that id. <br />");
		}
	}
	else 
	{
		echo("Invalid template id.<br />");
	}


}
catch (Exception $e)
{
	
}