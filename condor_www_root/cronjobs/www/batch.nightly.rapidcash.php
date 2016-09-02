<?php
/**
 * Rapidcash night email list
 * 
 * @author Jason Gabriele <jason.gabriele@sellingsource.com>
 * 
 * @version
 * 	    1.0.0 Jul 11, 2006 - Jason Gabriele <jason.gabriele@sellingsource.com>
 */
define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');

require_once 'mysqli.1.php';

//$mode = 'RC';
//$mysql = new MySQLi_1('db101.ept.tss','sellingsource','password','rc_olp','3317');
$mode = 'LIVE';
$mysql = new MySQLi_1('reader.olp.ept.tss','sellingsource','password','olp','3307');

$yesterday = mktime(0,0,0,date("m"),date("d")-1,date("Y"));
$begin_date = date("Ymd000000", $yesterday);
$end_date = date("Ymd235959", $yesterday);

$sql = "SELECT 
			p.email, 
			date_format(c.created_date,'%m/%d/%y %k:%i:%s') as date
		FROM 
			campaign_info as c
		JOIN 
			personal p USING (application_id)
		WHERE 
			c.created_date BETWEEN '{$begin_date}' AND '{$end_date}'
		AND 
			c.promo_id IN (28085, 28447)";

$res = $mysql->query($sql);

$tx = new OlpTxMailClient(false,$mode);

$recipients = array(
//	array("email_primary_name" => "Test Email", "email_primary" => "adam.englander@sellingsource.com"),
	array("email_primary_name" => "Denise", "email_primary" => "denise.tipton@partnerweekly.com"),
	array("email_primary_name" => "Eric L", "email_primary" => "elax@leadfinancialgroup.com"),
	array("email_primary_name" => "Rebecca", "email_primary" => "Rebecca@leadfinancialgroup.com"),
	array("email_primary_name" => "Dan", "email_primary" => "Dan@leadfinancialgroup.com")

);

if($res->Row_Count() > 0)
{
	$fp = fopen("/tmp/rapidcash.csv","w");
	fwrite($fp,"Email\tDate Received\n");

	while($row = $res->Fetch_Object_Row())
	{
		fwrite($fp,$row->email."\t".$row->date."\n");
	}
	fclose($fp);

	//Send email
	$header = array("sender_name"       => "Selling Source <no-reply@sellingsource.com>",
				    "subject" 	        => "Apps for Rapidcashnow for " . date("m/d/y",$yesterday),
				    "site_name" 	    => "sellingsource.com",
				    "message"			=> "Apps for Rapidcashnow for " . date("m/d/y",$yesterday));

	$out = file_get_contents('/tmp/rapidcash.csv');

	$attach = array(
		'method' => 'ATTACH',
		'filename' => 'rapidcash.csv',
		'mime_type' => 'text/plain',
		'file_data' => gzcompress($out),
		'file_data_size' => strlen($out),
	);
					    
	foreach($recipients as $r)
	{
		$data = array_merge($r,$header);
		$result = $tx->sendMessage('live', 'CRON_EMAIL', $data['email_primary'], '', $data, array($attach));
	}
	$ftp = ftp_connect('66.246.242.198');
	if($ftp && ftp_login($ftp,'SellingSource','*fagTdl8Gl'))
	{
		ftp_put($ftp,date('Ymd_',$yesterday).'_cashadvancesweeps.csv', '/tmp/rapidcash.csv', FTP_ASCII);
		ftp_close($ftp);
	}
	else 
	{
		echo("Could not connect to ftp server\n");
	}
	unlink("/tmp/rapidcash.csv");
}
else
{
	$header = array("sender_name"       => "Selling Source <no-reply@sellingsource.com>",
				    "subject" 	        => "Apps for rapidcash for " . date("m/d/y",$yesterday),
				    "site_name" 	    => "sellingsource.com",
				    "message"			=> "There are no apps for " . date("m/d/y",$yesterday));
					    
	foreach($recipients as $r)
	{
		$data = array_merge($r,$header);
		$result = $tx->sendMessage('live', 'CRON_EMAIL', $data['email_primary'], '', $data);
	}		    
	exit();
}
