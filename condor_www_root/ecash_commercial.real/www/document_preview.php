<?php

// include our config file
require_once('config.php');
require_once(LIB_DIR.'common_functions.php');
require_once(COMMON_LIB_DIR."pay_date_calc.3.php");
require_once(SQL_LIB_DIR . "util.func.php");
require_once(SERVER_CODE_DIR . "server_factory.class.php");
require_once (LIB_DIR . "/Document/Document.class.php");

$session_id =  isset($_REQUEST['ssid']) ? $_REQUEST['ssid'] : null;

$request = (object) $_REQUEST;

$server = Server_Factory::get_server_class(null,$session_id);
$server->Process_Data($request);

eCash_Document::Singleton($server,$request)->Preview_Document($_REQUEST['application_id'],$_REQUEST['document_id']);
