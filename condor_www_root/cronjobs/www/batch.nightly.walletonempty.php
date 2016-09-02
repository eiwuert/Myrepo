
<?
/**
 * Report Cron
 * Author: Vinh Trinh
 * Date: 10/3/2007
 *
 * Desc: Script generates a daily report for promo id's 30523 and 30568
 */
define(BFW_CODE_DIR,'/virtualhosts/bfw.1.edataserver.com/include/code/');
require_once(BFW_CODE_DIR.'crypt_config.php');
require_once(BFW_CODE_DIR.'crypt.singleton.class.php');
require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');


define(MODE,'SLAVE');

//Define database mode for server.php
$datamode = 'REPORT';
$mailmode = 'LIVE';

require_once BFW_CODE_DIR.'server.php';
require_once 'mysql.4.php';

//== Main ======================================================

// Initialize Variables
$promo_id1 = '30523';
$promo_id2 = '30568';

$now_time = strtotime(date("Ymd000000",time()));

$start_timestamp = strtotime('-1 days',$now_time);
$end_timestamp = strtotime("-0 days",$now_time);

$start_date = date("Ymd",$start_timestamp)."000000";
$end_date = date("Ymd",$end_timestamp)."000000";

$formated_start_date = date("m/d/Y H:i:s",$start_timestamp);
$formated_end_date = date("m/d/Y H:i:s",$end_timestamp);


// Grab the data
$promoReport1 = new PromoReport($datamode);
$data1 = $promoReport1->getData($promo_id1,$start_timestamp,$end_timestamp);

$promoReport2 = new PromoReport($datamode);
$data2 = $promoReport2->getData($promo_id2,$start_timestamp,$end_timestamp);


// Send out the Data
$csv1 = generate_csv($data1);
$csv2 = generate_csv($data2);
	
$recipients = array
(
	//array("email_primary_name" => "Test", "email_primary" => "adam.englander@sellingsource.com"),
	array("email_primary_name" => "Ronald May", "email_primary" => "ronald.may@absoluteroi.com"),
	array("email_primary_name" => "Daniel Collett", "email_primary" => "daniel.collett@absoluteroi.com"),
);

foreach($recipients as $recipient)
{	
	$subject = "Lead Report from Promo Id(s): $promo_id1";
	$message = "Attached are the Daily Leads from Promo Id(s): $promo_id1 .<br />\n";
	$message .= "Date Range: $formated_start_date to $formated_end_date. <br />\n";
	$message .= "This report is automatically generated, please do not reply. <br />\n";	
	
	$attachment = array("filename" => "leads_$promo_id1.csv", "content" => $csv1);
	mail_out($recipient,$subject,$message,$attachment,$mailmode);
}


foreach($recipients as $recipient)
{	
	$subject = "Lead Report from Promo Id(s): $promo_id2 - Date Range: $formated_start_date to $formated_end_date";
	$message = "Attached are the Daily Leads from Promo Id(s): $promo_id2 .<br />\n";
	$message .= "Date Range: $formated_start_date to $formated_end_date.<br />\n";
	$message .= "This report is automatically generated, please do not reply.<br />\n";
		
	$attachment = array("filename" => "leads_$promo_id2.csv", "content" => $csv2);
	mail_out($recipient,$subject,$message,$attachment,$mailmode);
}


//=============================================================


function mail_out($recipient,$subject,$message,$attachment,$mode)
{
	
	$tx = new OlpTxMailClient(false,$mode);
	$header = array
	(
		"sender_name"	=> "Selling Source <no-reply@sellingsource.com>",
		"subject"		=> $subject,
		"site_name"		=> "sellingsource.com",
		"message"		=> $message
	);
	
	$attach = array(
		'method' => 'ATTACH',
		'filename' => $attachment['filename'],
		'mime_type' => 'text/plain',
		'file_data' => gzcompress($attachment['content']),
		'file_data_size' => strlen($out),
	);
					    
	$data = array_merge($recipient,$header);
	$result = $tx->sendMessage('live', 'CRON_EMAIL', $data['email_primary'], '', $data, array($attach));
}

function generate_csv($data)
{
	
	if(sizeof($data) >= 1)
	{
		$csv_output = "Number,".implode(",",array_keys($data[0]))."\n";
		foreach($data as $index => $fields)
		{
			$csv_output .= $index + 1 . ",".implode(",",array_values($fields))."\n";
		}
		
		return $csv_output;
	}
	else 
	{
		return "No Results";
	}
}
	
function out($input)
{
	echo "<pre>";
	print_r($input);
	echo "</pre>";
}

/**
 * Reusable class to grab data by PROMO ID.
 *
 */
class PromoReport
{
	private $sql;
	private $server;
	private $promo_id;
	private $status_counter;
	private $crypt_config;
	private $cryptSingleton;
	
	public function __construct($mode='REPORT')
	{
		$crypt_config 		= Crypt_Config::Get_Config('LIVE');
		$this->cryptSingleton 	= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);
		
		$this->server = Server::Get_Server($mode,'BLACKBOX');
		$this->sql = new MySQL_4($this->server['host'],$this->server['user'],$this->server['password']);
		$this->sql->Connect();
		
	}
	
	public function getData($promo_id='',$start_timestamp,$end_timestamp)
	{
		
		$increment = 60*15; // 10-minute chunks
		$data = array();
		$chunk_start = $start_timestamp;
		$chunk_stop = $start_timestamp + $increment;
		$this->promo_id = $promo_id;
		
		
		while($chunk_stop <= $end_timestamp)
		{
		
			$data = array_merge($this->queryRange($chunk_start,$chunk_stop),$data);
			$chunk_start = $chunk_stop + 1;
			$chunk_stop = $chunk_start + $increment - 1;
		}
		
		return $data;
	}
	
	private function queryRange($start_timestamp,$end_timestamp)
	{
		$rows = array();
		$soap_tables = array();
		
		$start_datetime = mysql_escape_string(date('YmdHis',$start_timestamp));
		$end_datetime = mysql_escape_string(date('YmdHis',$end_timestamp));
		$promo_id = mysql_escape_string($this->promo_id);

		$query = "
				SELECT
					a.application_type,
					c.application_id,
					date_format(c.modified_date,'%m/%d/%Y %T') as date,
					c.promo_id,
					c.ip_address,
					c.url,
					p.first_name,
					p.last_name,
					p.home_phone,
					p.drivers_license_number,
					p.drivers_license_state,
					p.military,
					p.email,
					r.address_1,
					r.address_2,
					r.city,
					r.state,
					r.zip
				FROM
					campaign_info c 
					JOIN application a using (application_id)
					JOIN personal_encrypted p using (application_id)
					JOIN residence r using (application_id)
				WHERE
					c.modified_date between $start_datetime and $end_datetime AND
					c.promo_id = {$promo_id}
			";
			
			
		$rows = array_merge($this->fetch_rows($query),$rows);

		return $rows;
	}
	
	private function status_output()
	{
		$this->status_counter++;
		
		echo ".";
		
		if($this->status_counter >= 100)
		{
			echo "<br />";
			$this->status_counter = 0;
		}

		ob_flush();
		flush();
	}
	
	private function get_soap_tables($start_date)
	{
		$rows = array();
		$soap_tables = array();
		$dates = array();
		$return_tables = array();
				
		$query = "
			SHOW TABLES FROM
				olp_archive
			LIKE
				'soap_data_log%'
		";
		
		$rows = $this->fetch_rows($query);
		
		//Make 2 arrays
		// 1 - one to hold all the names of all the soap tables,
		// 2 - one to hold just the dates of those soap tables
		foreach($rows as $index => $row)
		{
			$soap_tables[$index] = 'olp_archive.'.array_pop($row);
			$dates[$index] = str_replace('olp_archive.soap_data_log_','',$soap_tables[$index]);	
		}
		
		// Put the current table at the end of the soap table list
		$soap_tables[] = 'soap_data_log';
		
		//Using the start date, get the index to which tables we need to use.
		foreach($dates as $index => $date)
		{
			if($date > substr($start_date,0,8)) // Substring because start date is passed in as an entire timestamp.
			{
				$return_index = $index; // This will be used to determine which tables we will return
			}	
		}	
		
		// Get the soap table as well as the next one.
		($soap_tables[$return_index]) ? $return_tables[] = $soap_tables[$return_index] : null;
		($soap_tables[$return_index+1]) ? $return_tables[] = $soap_tables[$return_index+1] : null;

		return $return_tables;
	}
	
	private function fetch_rows($query)
	{
		$rows = array();
		$results = $this->sql->query($this->server['db'],$query);
		
		while($row = $this->sql->fetch_array_row($results))
		{
			$rows[] = $row;

		}
		
		return $rows;
	}
}



?>


