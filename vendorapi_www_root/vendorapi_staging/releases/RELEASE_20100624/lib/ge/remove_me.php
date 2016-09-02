<?php

require_once 'prpc/client.php';
require_once 'ge/form_class.php';
define ('PRPC_SERVER','prpc://emailremove.1.soapdataserver.com/emailremove.php');

$form = new Form('../removeme.html');

if ( ! isset( $_REQUEST['email']) ) {
	$form->Display($fields);
} else {
	$client = new Prpc_Client (PRPC_SERVER, true);
	$response = $client->Remove($_REQUEST['email']);

	$fields = new StdClass();
	$fields->status_msg = $response[1];
	$form->Display($fields);
}

?>
