<?php
/**
 * *Retrieves lead information for www.paydaydynamics.com
 * Uncomment $server  and $recipients when ready to go live
 */

define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

require_once('mysql.4.php');

require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
require_once(BFW_CODE_DIR.'server.php');
require_once(BFW_CODE_DIR.'crypt_config.php');
require_once(BFW_CODE_DIR.'crypt.singleton.class.php');

$crypt_config 	= Crypt_Config::Get_Config('LIVE');
$cryptSingleton 	= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);

$search_url = "paydaydynamics.com";

//$server = Server::Get_Server('RC','BLACKBOX');
//$mode = 'RC';
// WHEN READY TO GO LIVE;
$server = Server::Get_Server('SLAVE','BLACKBOX');
$mode = 'LIVE';

$sql = new MySQL_4($server['host'], $server['user'], $server['password'],FALSE);
$sql->Connect();

$yesterday = mktime(0,0,0,date("m"),date("d")-1,date("Y"));
$date2 = date("Y-m-d 00:00:00", $yesterday);
$date1 = date("Y-m-d 23:59:59", $yesterday);

// Get the license key for the site first. Searching by license key is indexed, where
// by site is not.
$query = "
	SELECT
		license
	FROM
		license_map
	WHERE
		site_name = '$search_url'
		AND mode = 'LIVE'";

$result = $sql->Query('management', $query);

if($row = $sql->Fetch_Object_Row($result))
{
	$license_key = $row->license;
}

$sql->Free_Result($result);

if(!isset($license_key))
{
	die('Failed to retreive the license key for ' . $search_url);
}

// Prepare Query
$query = "
	SELECT 
		p.first_name,
		p.last_name,
		p.email,
		r.address_1,
		r.city,
		r.state,
		r.zip,
		p.home_phone,
		p.date_of_birth,
		c.ip_address,
		date_format(c.created_date,'%m/%d/%y %k:%i:%s') as date
	FROM 
		campaign_info c 
		INNER JOIN application a USING (application_id)
		INNER JOIN personal_encrypted p USING (application_id)
		INNER JOIN residence r USING (application_id)
	WHERE
		c.license_key = '{$license_key}'
		AND c.created_date BETWEEN '{$date2}' and '{$date1}'";

// Gather Data
$results = $sql->Query($server['db'],$query);

$rowcount = $sql->Row_Count($results);

// Manage Data
$csv = "First Name,Last Name,Email,Address,City,State,Zip,Home Phone,Date of Birth,IP Address,Date Received\n";
while($row = $sql->Fetch_Array_Row($results)){
	$row['date_of_birth'] = $cryptSingleton->decrypt($row['date_of_birth']);
	
	foreach($row as $key => $value){
		$csv .= "$value,";
	}
	$csv .= "\n";
}

if($rowcount == 1){
	$subject = "- $rowcount - www.paydaydynamics.com lead for $date2 -> $date1";
}
else{
	$subject = "- $rowcount - www.paydaydynamics.com leads for $date2 -> $date1";	
}


// Prepare Email
//$mail = new Prpc_Client('prpc://smtp.2.soapdataserver.com/ole_smtp.1.php');
$tx = new OlpTxMailClient(false,$mode);

$header = array
(
"sender_name"           => "Selling Source <no-reply@sellingsource.com>",
"subject" 	        	=> $subject,
"site_name" 	        => "sellingsource.com",
"message"				=> $subject
);

$recipients = array
(
	array("email_primary_name" => "Jeff", "email_primary" => "jeff@paydayjunction.com"),
	array("email_primary_name" => "rodrigo", "email_primary" => "rodrigo@paydayjunction.com"),
	array("email_primary_name" => "deguz911", "email_primary" => "deguz911@gmail.com"),
	array("email_primary_name" => "delvec32", "email_primary" => "delvec32@gmail.com")
//	array("email_primary_name" => "Adam Englander", "email_primary" => "adam.englander@sellingsource.com")

);

$attach = array(
	'method' => 'ATTACH',
	'filename' => 'pdleads.csv',
	'mime_type' => 'text/plain',
	'file_data' => gzcompress($csv),
	'file_data_size' => strlen($csv),
);

foreach($recipients as $r){
	$data = array_merge($r,$header);
	
	try
	{
		$result = $tx->sendMessage('live', 'PDDLEADS_CRON', $data['email_primary'], '', $data, array($attach));
	}
	catch(Exception $e)
	{
		$result = FALSE;
	}
	//$data['attachment_id'] = $mail->Add_Attachment($csv, 'application/text', "pdleads.csv", "ATTACH");
	
	
	//$result = $mail->Ole_Send_Mail("PDDLEADS_CRON", 17176, $data);
	if($result)
	{
		print "EMAIL HAS BEEN SENT TO: ".$r['email_primary']."\n";
	}
	else
	{
		print "ERROR SENDING EMAIL TO: ".$r['email_primary']."\n";
	}

}

?>
