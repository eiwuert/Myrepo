<?php

include_once (DIR_LIB . "session_pool.1.php");

// Do a security check and re-establish current ecash session
if(!isset($_COOKIE['nfsid']) || strlen($_COOKIE['nfsid']) < 26)
{
	die ("<h3>You are not logged in. Your session may have expired.</h3>");
}
$session_id = $_COOKIE['nfsid'];
$session_obj = new Session_1($session_id);
if(!isset($_SESSION["security_6"]["login_time"]))
{
	die ("<h3>You are not logged in. Your session may have expired.</h3>");
}

?>
