<?php
require_once ("mysql.3.php");
require_once ("error.2.php");
require_once ('security.3.php');
require_once ("lib_mail.1.php");
require_once ("crypt.3.php");

ini_set ('magic_quotes_runtime', 0);
ini_set ('implicit_flush', 1);
ini_set ('output_buffering', 0);
ob_implicit_flush ();
list ($ss, $sm) = explode (" ", microtime ());
// Make sure we keep running even if user aborts
ignore_user_abort (TRUE);

// Let it run forever
set_time_limit (0);

$mode = strtoupper($argv[1]);

// Connection information
switch($mode)
{
//RC
 case "RC":
	 define ("HOST", "ds001.ibm.tss");
	 define ("USER", "admin");
	 define ("PASS", "%selling\$_db");
	 break;

//LIVE
 case "LIVE":
	 define ("HOST", "ds001.ibm.tss");
	 define ("USER", "admin");
	 define ("PASS", "%selling\$_db");
	 break;

//TEST
 case "LOCAL":
 default:
	 define ("HOST", "localhost");
	 define ("USER", "root");
	 define ("PASS", "");
	 break;
}

// Build the sql object
$sql = new MySQL_3 ();

// Try the connection
//print "Connecting...";
$result = $sql->Connect ("BOTH", HOST, USER, PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
Error_2::Error_Test ($result, TRUE);
//print "Done\n";

$yesterday = strtotime ("-1 day");

$mysql_start = date("Ymd000000", $yesterday);
$mysql_end = date("Ymd235959", $yesterday);


// Pull the user information
$query = "select * from customer where created_date between '".$mysql_start."' AND '".$mysql_end."' and action_id = 400";
//echo "{$query}\n";
$result = $sql->Query ('teleweb', $query, Debug_1::Trace_Code (__FILE__, __LINE__));
Error_2::Error_Test ($result, TRUE);

$total_found = $sql->Row_Count($result);
//echo "Count: {$total_found}\n";
//die();


if($total_found)
{
//build the file with the rows
$path = "/virtualhosts/cronjobs/teleweb/file_out/";
$file_date = date("md", $yesterday);
$file_name =  "TEL1" . $file_date;
$full_file_name = $path . $file_name;
$fp = fopen($full_file_name, "w");

$crypt = new Crypt_3();


while ($row = $sql->Fetch_Object_Row ($result))
{
	preg_match('/^(\d{3})(\d{7})$/', $row->home_phone, $phone_matches);
	$area_code = $phone_matches[1];
	$phone = $phone_matches[2];
	
	preg_match('/^\d{2}(\d{2})(\d{2})/', $row->cc_exp_date, $cc_matches);
	$exp_year = $cc_matches[1];
	$exp_month = $cc_matches[2];

	preg_match('/^\d{2}(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $row->created_date, $date_matches);
	$trans_date = $date_matches[1] . $date_matches[2] . $date_matches[3];
	$trans_time = $date_matches[4] . $date_matches[5] . $date_matches[6];

	$line = array();
	$line[] = '3PUWIN';//'VPIDIRCGY', 'VPICERTY', '3PUWIN';						//1
	$line[] = $row->last_name;
	$line[] = $row->first_name;
	$line[] = $row->address;
	$line[] = $row->address2;
	$line[] = $row->city;
	$line[] = $row->state;
	$line[] = $row->zip;
	$line[] = $area_code;
	$line[] = $phone;										//10
	$line[] = $row->cc_type;
	$line[] = $crypt->Decrypt($row->cc_crypt, 'teleweb');
	$line[] = $exp_month . $exp_year;
	$line[] = 'Y';
	$line[] = 'Y';
	$line[] = '1';
	$line[] = 'A';
	$line[] = '';
	$line[] = '';
	$line[] = '';											//20
	$line[] = '';
	$line[] = 'T*TLW*LNS*TWEB2*WIN'; //'T*TLW*LNS*TWEB*DCC';
	$line[] = '';
	$line[] = '';
	$line[] = '';
	$line[] = $trans_date;
	$line[] = $trans_time;
	$line[] = 'I';
	$line[] = $row->cc_name;;
	$line[] = '';											//30
	$line[] = '';
	$line[] = '';
	$line[] = '';
	$line[] = '';
	$line[] = '';
	$line[] = '';
	$line[] = '';
	$line[] = '';
	$line[] = $row->assigned_csr . '*' . $row->created_date;
	$line[] = '';											//40
	$line[] = '';
	$line[] = $row->customer_id;
	$line[] = $row->email;
	$line[] = $row->broadband;
	$line[] = $row->ok_to_mail;
	$line[] = $row->cvv2;
	$line[] = $row->cc_addr;
	$line[] = $row->cc_addr_2;
	$line[] = $row->cc_city;
	$line[] = $row->cc_state;								//50
	$line[] = $row->cc_zip;
	$line[] = '';

	$csv = implode(",", $line);
	//echo "{$csv}\n";
	//die;
	fwrite($fp, $csv . "\r\n");
}

fclose($fp);


//GPG/PGP encrypt it
//exec("gpg -e --homedir /home/release/.gnupg -r certs@videoprofessor.com --always-trust $filename", $rc);
exec("gpg --homedir /home/release/.gnupg -q -e -r certs@videoprofessor.com --always-trust {$full_file_name}", $output, $rc);
//print_r($rc);
if(!$rc)
{

//send it off
$fp = fopen($full_file_name . ".gpg", "r");
$url = "ftp://telftp:6W5y-w+T@205.170.85.220:21/infiles/{$file_name}.gpg";

$ch = curl_init();
curl_setopt($ch, CURLOPT_PROXY, "ftp://10.0.0.1:3128");
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_UPLOAD, 1);
curl_setopt($ch, CURLOPT_INFILE, $fp);
curl_setopt($ch, CURLOPT_FTPASCII, 0);
curl_setopt($ch, CURLOPT_INFILESIZE, filesize($full_file_name . ".gpg"));

$result = curl_exec($ch);
curl_close($ch);

//set the status to sent in the database
$query = "update customer set action_id = 402 where created_date between '".$mysql_start."' AND '".$mysql_end."' and action_id = 400";
//echo "{$query}\n";
$result = $sql->Query ('teleweb', $query, Debug_1::Trace_Code (__FILE__, __LINE__));
Error_2::Error_Test ($result, TRUE);

	echo "Deleting file: {$full_file_name}\n";
	exec("rm $full_file_name");
}


}
else
{
	echo "{$total_found} records found between {$mysql_start} and {$mysql_end}\n";
}

?>
