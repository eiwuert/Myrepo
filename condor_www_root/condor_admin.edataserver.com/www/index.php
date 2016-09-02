<?php 

require_once('config.php');
require_once(LIB_DIR . 'common_functions.php');

try
{
	
	//these two lines determine what comm protocol to use
	//require_once WWW_DIR.'comm_prpc_client.php';
	//$comm = new Comm_Prpc_Client();
	require_once WWW_DIR.'comm_class.php';
	$comm = new Comm_Class();
		
	$session_id = ($_REQUEST['ssid']) ? $_REQUEST['ssid'] : null;
	$transport = $comm->Process_Data((object)$_REQUEST, $session_id,(object)$_FILES);
	$top_level = $transport->Get_Next_Level();
	
	switch($top_level)
	{
		case "inline":
			$page = new Display_Inline();
			break;
		case "popup":
			$page = new Display_Popup();
			break;
		case "close_pop_up":
			include(WWW_DIR . "close_pop_up.html");
			exit;
			break;
		case "download":
			$transport->download = true;
			// fall through ok
		case "application":
			$page = new Display_Application();
			break;
		case "login":
			$page = new Display_Login();
			break;
		case "exception":
			include(CLIENT_VIEW_DIR . "exception.html");
			exit;
			break;
		default:
			$page = new Display_Unknown();
			break;
	}

	$page->Do_Display($transport);

	print($debug_output);

}
catch(Exception $e)
{
	$exception = new Display_Exception();
	$exception->Do_Display($e);
}

?>