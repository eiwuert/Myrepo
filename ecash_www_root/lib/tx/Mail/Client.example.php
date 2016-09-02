<?php

require_once('tx/Mail/Client.php');

$email = 'rebel75cell@gmail.com, brian.gillingham@gmail.com, randy.klepetko@sbcglobal.net';
//$track_key = 'test123';
$suppression_list[] = 0;

$data = array(
    "email_primary" => $email,
    "email_primary_name" => "Test",
    "name" => "Person",
    "applicationid" => "123456",
    "amount" => '$300',
    "date" => "01/01/2007",
    "confirm" => "test",
    "csphone" => "1-800-397-7706",
    "username" => "username",
    "password" => "password",
    "site" => "ameriloan.com",
    "site_name" => "ameriloan.com",
    "name_view" => "Ameriloan",
);

$template = "ECASH_DAILY_CASH_REPORT";
//$template = '';

$tx = new tx_Mail_Client(true);
$res = $tx->test();
//var_dump($res);
//exit;
$res = $tx->sendMessage('live', $template, $data['email_primary'],"DTiip49esVl1Lg,JdQkBItrwzt7" , $data);

var_dump($res);

?>
