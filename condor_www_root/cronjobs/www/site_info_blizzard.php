<?
/**
 * Retrieves lead information for Blizzard Interactive for the previous day
 * This code only grabs the code from our marketing sites and does not include
 * the leads from soap sites.
 */

define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

require_once(BFW_CODE_DIR.'server.php');

require_once('mysql.4.php');
require_once('mysqli.1.php');
include_once('prpc/client.php');
require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');

require_once(BFW_CODE_DIR.'crypt_config.php');
require_once(BFW_CODE_DIR.'crypt.singleton.class.php');

$crypt_config 	= Crypt_Config::Get_Config('LIVE');
$cryptSingleton 	= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);


// WHEN READY TO GO LIVE;
$server = Server::Get_Server('SLAVE','BLACKBOX');
$mode = 'LIVE';
// For testing
//$server = Server::Get_Server('REPORT','BLACKBOX');
//$server = Server::Get_Server('RC','BLACKBOX');
//$mode = 'RC';

$sql = new MySQL_4($server['host'], $server['user'], $server['password'],FALSE);
$sql->Connect();

$yesterday = mktime(0,0,0,date("m"),date("d")-1,date("Y"));
$today = mktime(0,0,0,date("m"),date("d"),date("Y"));  // this variable is not used
$date2 = date("Y-m-d 00:00:00", $yesterday);
$date1 = date("Y-m-d 23:59:59", $yesterday);

$query1 = "select distinct unique_id from soap_data_log where date_created between '$date2' and '$date1'";
// Gather Data
$results = $sql->Query($server['db'],$query1);
// Manage Data
$unique_ids = "";
$myCount = 0;
while($row = $sql->Fetch_Array_Row($results))
{
	if($myCount != 0){
		$unique_ids .= ",";
	}
	foreach($row as $key => $value){
		$unique_ids .= "'$value'";
		$myCount++;
	}
}

// Only if the myCount has something in it should we execute something, otherwise just skip it for the night
if($myCount != 0)
{
	// create the CSV variable and prefill it with data
	$csv = "application_id,ip_address,url,promo_id,promo_sub_code,modified_date,winner,first_name,middle_name,last_name,home_phone,cell_phone,fax_phone,email,date_of_birth,social_security_number,drivers_license_number,best_call_time,drivers_license_state\n";
	
	
	
	// Prepare Query
		$query = "
		SELECT 
			p.application_id,
			ci.ip_address,
			ci.url,
			ci.promo_id,
			ci.promo_sub_code,
			p.modified_date,
			bp.winner,
			p.first_name,
			p.middle_name,
			p.last_name,
			p.home_phone,
			p.cell_phone,
			p.fax_phone,
			p.email,
			p.date_of_birth,
			p.social_security_number,
			p.drivers_license_number,
			p.best_call_time,
			p.drivers_license_state
		FROM 
			personal_encrypted p, campaign_info ci , blackbox_post bp, application a
		WHERE 
			p.application_id = ci.application_id
			AND p.application_id = a.application_id
			AND p.application_id = bp.application_id
			AND bp.winner like 'bi%'
			AND vendor_decision = 'ACCEPTED'
			AND a.session_id not in ($unique_ids)
			AND date_created BETWEEN '$date2' and '$date1'
		ORDER 
			by p.modified_date desc";
	
	
	// Gather Data
	$results = $sql->Query($server['db'],$query);
	
	$rowcount = $sql->Row_Count($results);
	
	// Manage Data
	
	while($row = $sql->Fetch_Array_Row($results))
	{
		$row['social_security_number'] = $cryptSingleton->decrypt($row['social_security_number']);
		$row['date_of_birth'] = $cryptSingleton->decrypt($row['date_of_birth']);
		
		foreach($row as $key => $value)
		{
			$csv .= "$value,";
		}
		$csv .= "\n";
	}
	
	if($rowcount == 1)
	{
		$subject = "- $rowcount - www.blizzardinteractive.com lead for $date2 -> $date1";
	}
	else{
		$subject = "- $rowcount - www.blizzardinteractive.com leads for $date2 -> $date1";	
	}
	
	
	// Prepare Email
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
//	array("email_primary_name" => "Adam Englander", "email_primary" => "adam.englander@sellingsource.com"),
	array("email_primary_name" => "Christian Esguerra", "email_primary" => "ce@blizzardi.com"),
	array("email_primary_name" => "Hope Pacariem", "email_primary" => "hope.pacariem@sellingsource.com"),
	array("email_primary_name" => "Peter Finn", "email_primary" => "Peter.finn@partnerweekly.com")
	
	);
	
	$attach = array(
		'method' => 'ATTACH',
		'filename' => 'blizzard_leads.csv',
		'mime_type' => 'text/plain',
		'file_data' => gzcompress($csv),
		'file_data_size' => strlen($csv),
	);
	
	foreach($recipients as $r)
	{
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
//			print "\r\nEMAIL HAS BEEN SENT TO: ".$r['email_primary']." .\n";
//			print "\r\nSubject: ".$subject." .\n";
		}
		else
		{
			print "\r\nERROR SENDING EMAIL TO: ".$r['email_primary']." .\n";
			print "\r\nSubject: ".$subject." .\n";
		}
	
	}
} 
else 
{

			print "\r\n" . "Emails did not generate, there were no unique IDs found in the query" ."\n";
				
}
?>



