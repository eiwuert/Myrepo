#!/usr/bin/php
<?php

/**
 * retrieves the login and unencrypted password for a given application
 */
 
if($argc > 2) { $application_id = $argv[2]; $company = strtolower($argv[1]);}
else Usage($argv);

require_once("../www/config.php");
define ('CUSTOMER_LIB', BASE_DIR . "customer_lib/{$company}/");
require_once("mini-server.class.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");
require_once(SQL_LIB_DIR . "get_mysqli.func.php");
require_once(LIB_DIR . 'common_functions.php');
require_once('crypt.3.php');

$query = "select login, crypt_password 
		from application 
		join login on (application.login_id = login.login_id) 
		where application_id = {$application_id}";

$mysqli = get_mysqli();
$res = $mysqli->Query($query);

while ($row = $res->Fetch_Row()) {
	echo "-----\nApplication ID: {$application_id}\n";
	echo "Username/Password: {$row[0]}/". crypt_3::Decrypt($row[1]) ."\n-----\n";
}

exit;

function Usage($argv)
{
        echo "Usage: {$argv[0]} [ic|clk] [application_id]\n";
        exit;
}