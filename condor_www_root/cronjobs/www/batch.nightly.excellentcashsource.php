<?php
/**
 * Smartcashloans nightly email list
 * 
 * @author Jason Gabriele <jason.gabriele@sellingsource.com>
 * 
 * @version
 * 	    1.0.0 Jul 11, 2006 - Jason Gabriele <jason.gabriele@sellingsource.com>
 */

define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

require_once 'mysqli.1.php';

require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
require_once(BFW_CODE_DIR.'crypt_config.php');
require_once(BFW_CODE_DIR.'crypt.singleton.class.php');

$crypt_config 	= Crypt_Config::Get_Config('LIVE');
$cryptSingleton 	= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);

//$mode = 'RC';
//$mysql = new MySQLi_1('db101.ept.tss','sellingsource','password','rc_olp','3317');
$mode = 'LIVE';
$mysql = new MySQLi_1('reporting.olp.ept.tss','sellingsource','password','olp','3306');


$yesterday = mktime(0,0,0,date("m"),date("d")-1,date("Y"));
$begin_date = date("Ymd000000", $yesterday);
$end_date = date("Ymd235959", $yesterday);

$sql = "SELECT 
			p.first_name, 
			p.last_name,
			p.email,
			r.address_1,
			r.city,
			r.state, 
			r.zip, 
			p.home_phone, 
			p.date_of_birth, 
			date_format(c.created_date,'%m/%d/%y %k:%i:%s') as date, 
			c.ip_address
		FROM 
			campaign_info as c 
		JOIN personal_encrypted p USING (application_id)
		JOIN residence r USING (application_id)
		WHERE 
			c.created_date BETWEEN '{$begin_date}' AND '{$end_date}'
		AND 
			c.promo_id = 30145
";
$res = $mysql->query($sql);

$tx = new OlpTxMailClient(false,$mode);

$recipients = array(
//	array("email_primary_name" => "Adam", "email_primary" => "adam.englander@sellingsource.com"),
	array("email_primary_name" => "Rodrigo", "email_primary" => "rodrigo@paydayjunction.com"),
	array("email_primary_name" => "Jeff", "email_primary" => "jeff@paydayjunction.com"),
	array("email_primary_name" => "Deguz", "email_primary" => "deguz911@gmail.com"),
	array("email_primary_name" => "Delvec", "email_primary" => "delvec32@gmail.com")
);

if($res->Row_Count() > 0)
{
	$fp = fopen("/tmp/excellentcashsource.csv","w");
	fwrite($fp,"First Name,Last Name,Email,Address,City,State,Zip,Home Phone,Date of Birth,IP Address,Date Received\n");
	while($row = $res->Fetch_Object_Row())
	{
		$row->date_of_birth = $cryptSingleton->decrypt($row->date_of_birth);
		
		fwrite($fp,"$row->first_name,$row->last_name,$row->email,".
			"$row->address_1,$row->city,$row->state,$row->zip,$row->home_phone,$row->date_of_birth,".
			"$row->ip_address,$row->date\n");
	}
	fclose($fp);
	//Send email
	$header = array("sender_name"       => "Selling Source <no-reply@sellingsource.com>",
				    "subject" 	        => "Apps for Mariposa Media for " . date("m/d/y",$yesterday),
				    "site_name" 	    => "sellingsource.com",
				    "message"			=> "Apps for Mariposa Media for " . date("m/d/y",$yesterday));

	$out = file_get_contents('/tmp/excellentcashsource.csv');

	$attach = array(
		'method' => 'ATTACH',
		'filename' => 'excellentcashsource.csv',
		'mime_type' => 'text/plain',
		'file_data' => gzcompress($out),
		'file_data_size' => strlen($out),
	);
					    
	foreach($recipients as $r)
	{	
		$data = array_merge($r,$header);
		$result = $tx->sendMessage('live', 'CRON_EMAIL', $data['email_primary'], '', $data, array($attach));
	}

	unlink("/tmp/excellentcashsource.csv");
}
else
{
	$header = array("sender_name"       => "Selling Source <no-reply@sellingsource.com>",
				    "subject" 	        => "Apps for Mariposa Media for " . date("m/d/y",$yesterday),
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
