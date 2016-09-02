<?php

define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
require_once(BFW_CODE_DIR.'server.php');
require_once('mysql.4.php');

require_once(BFW_CODE_DIR.'crypt_config.php');
require_once(BFW_CODE_DIR.'crypt.singleton.class.php');

$crypt_config 	= Crypt_Config::Get_Config('LIVE');
$cryptSingleton 	= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);

//$mode = 'RC';
//$server = Server::Get_Server('RC','BLACKBOX');

// WHEN READY TO GO LIVE;
$mode = 'LIVE';
$server = Server::Get_Server('REPORT', 'BLACKBOX');

$sql = new MySQL_4($server['host'], $server['user'], $server['password'], FALSE);
$sql->Connect();

$yesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
$start = date('Y-m-d 00:00:00', $yesterday);
$end = date('Y-m-d 23:59:59', $yesterday);

$event_log_table = date('Ym', $yesterday);

// If we ever do more than one site, we'll need to modify the query
$query = "
	SELECT
		CONCAT_WS(' ', first_name, last_name) AS name,
		cell_phone,
		ip_address,
		url,
		DATE_FORMAT(el.date_created, '%Y-%m-%d %H:%i:%s') AS date_created
	FROM
		event_log_{$event_log_table} el
		INNER JOIN events e ON el.event_id = e.event_id
		INNER JOIN event_responses r ON el.response_id = r.response_id
		INNER JOIN personal_encrypted p ON el.application_id = p.application_id
		INNER JOIN campaign_info ci ON el.application_id = ci.application_id
	WHERE
		e.event = 'DUPE_CELL_CHECK' AND r.response = 'PASS'
		AND el.date_created BETWEEN '{$start}' AND '{$end}'
		AND p.last_name IS NOT NULL AND p.last_name != ''";

$results = $sql->Query($server['db'], $query);

$csv = "Name,Cell Phone,IP Address,Originating URL,Date Received\n";
while($row = $sql->Fetch_Array_Row($results))
{
	$csv .= implode(',', $row) . "\n";
}

$subject = 'lifepayday.com batch file for ' . date('m/d/Y', $yesterday);



// Prepare Email
$tx = new OlpTxMailClient(false,$mode);

$header = array(
	'sender_name'	=> 'Selling Source <no-reply@sellingsource.com>',
	'subject'		=> $subject,
	'site_name'		=> 'sellingsource.com',
	'message'		=> $subject . "\n" . $sql->Row_Count($results) . ' total leads found.'
);

$recipients = array(
//	array('email_primary_name' => 'Test',		'email_primary' => 'adam.englander@sellingsource.com'),
	array('email_primary_name' => 'Chris Barmonde',		'email_primary' => 'christopher.barmonde@sellingsource.com'),
	array('email_primary_name' => 'Jon Lowry',			'email_primary' => 'jon.lowry@mskg.com'),
	array('email_primary_name' => 'Crystal Dougherty',	'email_primary' => 'crystal.dougherty@mskg.com'),
);

$attach = array(
	'method' => 'ATTACH',
	'filename' => 'lifepayday' . date('Ymd', $yesterday) . '.csv',
	'mime_type' => 'text/plain',
	'file_data' => gzcompress($csv),
	'file_data_size' => strlen($csv),
);

foreach($recipients as $r)
{
	$data = array_merge($r, $header);

	try
	{
		//PDDLEADS_CRON is a very, very generic template, so we'll just use it.
		$result = $tx->sendMessage('live', 'PDDLEADS_CRON', $data['email_primary'], '', $data, array($attach));
	}
	catch(Exception $e)
	{
		$result = FALSE;
	}

	if($result)
	{
		echo "EMAIL HAS BEEN SENT TO: {$r['email_primary']}\n";
	}
	else
	{
		echo "ERROR SENDING EMAIL TO: {$r['email_primary']}\n";
	}
}

?>
