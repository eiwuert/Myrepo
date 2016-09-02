<?php
/**
 * CashAdvanceSweeps night email list
 * 
 * @author Jason Gabriele <jason.gabriele@sellingsource.com>
 * Updated for encryption 10/29/2007 - Vinh
 * @version
 * 	    1.0.0 Jul 11, 2006 - Jason Gabriele <jason.gabriele@sellingsource.com>
 */

require_once 'mysqli.1.php';

define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
require_once(BFW_CODE_DIR.'crypt_config.php');
require_once(BFW_CODE_DIR.'crypt.singleton.class.php');

//Get the mode
if(isset($argv[1]))
{
	$mode = strtoupper($argv[1]);
}
else
{
	// Calling scripts assume live so default to live
	$mode = 'LIVE';
}

$crypt_config 	= Crypt_Config::Get_Config('LIVE');
$cryptSingleton 	= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);

//$mysql = new MySQLi_1('monster.tss','olp','hochimin','olp','3326');//OLP Dev Database for testing
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
			personal_encrypted p USING (application_id)
		WHERE 
			c.created_date BETWEEN '{$begin_date}' AND '{$end_date}'
		AND 
			c.promo_id = 28868";

$res = $mysql->query($sql);

$tx = new OlpTxMailClient(false,$mode);

$recipients = array(
	array("email_primary_name" => "Denise", "email_primary" => "denise.tipton@partnerweekly.com"),
	array("email_primary_name" => "Eric L", "email_primary" => "elax@leadfinancialgroup.com"),
	array("email_primary_name" => "Rebecca", "email_primary" => "Rebecca@leadfinancialgroup.com"),
	array("email_primary_name" => "Dan", "email_primary" => "Dan@leadfinancialgroup.com")
//	array("email_primary_name" => "ADAM ENGLANDER", "email_primary" => "adam.englander@sellingsource.com") //Testing email address

);

if($res->Row_Count() > 0)
{
	$fp = fopen("/tmp/cashadvancesweeps.csv","w");
	fwrite($fp,"Email\tDate Received\n");

	while($row = $res->Fetch_Object_Row())
	{
		fwrite($fp,$row->email."\t".$row->date."\n");
	}
	fclose($fp);

	//Send email
	$header = array("sender_name"       => "Selling Source <no-reply@sellingsource.com>",
				    "subject" 	        => "Apps for CashAdvanceSweeps for " . date("m/d/y",$yesterday),
				    "site_name" 	    => "sellingsource.com",
				    "message"			=> "Apps for CashAdvanceSweeps for " . date("m/d/y",$yesterday));

	$out = file_get_contents('/tmp/cashadvancesweeps.csv');

	$attach = array(
		'method' => 'ATTACH',
		'filename' => 'cashadvancesweeps.csv',
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
		ftp_put($ftp,date('Ymd_',$yesterday).'_cashadvancesweeps.csv', '/tmp/cashadvancesweeps.csv', FTP_ASCII);
		ftp_close($ftp);
	}
	else 
	{
		echo("Could not connect to ftp server\n");
	}
	unlink("/tmp/cashadvancesweeps.csv");
}
else
{
	$header = array("sender_name"       => "Selling Source <no-reply@sellingsource.com>",
				    "subject" 	        => "Apps for CashAdvanceSweeps for " . date("m/d/y",$yesterday),
				    "site_name" 	    => "sellingsource.com",
				    "message"			=> "There are no apps for " . date("m/d/y",$yesterday));
					    
	foreach($recipients as $r)
	{
		$data = array_merge($r,$header);
		$result = $tx->sendMessage('live', 'CRON_EMAIL', $data['email_primary'], '', $data);
	}		    
	exit();
}
?>
