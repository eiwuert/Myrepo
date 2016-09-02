<?php

require_once ("mysql.3.php");
require_once ("bugout.1.php");

require_once ("include/batch_make.php");


$opts = getopt ('n:m:d:');

$GLOBALS ['bugout_level'] = (isset ($opts['d']) && is_numeric ($opts['d'])) ? $opts['d'] : 0;
bugout::msg ("Bugout level is {$bugout_level}");

$GLOBALS ['node'] = (isset ($opts['n'])) ? $opts['n'] : 'localhost';
bugout::msg ("Running against node {$node}");

$GLOBALS ['mode'] = (isset ($opts['m']) && strtoupper($opts['m']) == 'LIVE') ? 'LIVE' : 'RC';
bugout::msg ("Running in {$mode} mode");


$sql = new MySQL_3 ();
$result = $sql->Connect (NULL, $node, 'gecron', 'x10powerhouse', Debug_1::Trace_Code (__FILE__, __LINE__));
Error_2::Error_Test ($result, TRUE);

$promo = new stdClass ();
$bl = array ();
$bl_count = 0;

//$query = "insert into file_out (file_date) values (NOW())";
//$result = $sql->Query ('ge_batch', $query);
//Error_2::Error_Test ($result, TRUE);

$query = "select MAX(file_id) from file_out";
$result = $sql->Query ('ge_batch', $query);
Error_2::Error_Test ($result, TRUE);
$batch_id = $sql->Fetch_Column ($result, 0) + 1;

$query = "select file_date from file_out where file_id = ".($batch_id-1);
$result = $sql->Query ('ge_batch', $query);
Error_2::Error_Test ($result, TRUE);
$last_date = $sql->Fetch_Column ($result, 0);

$file_date = date ('Y-m-d H:i:s');

// CriticsChoice
$promo->site_code = "geccmem";
$promo->promo_code = '01004212';

$query = "SELECT"
    ."  MAX(orders.creation_stamp), "
    ."  person.firstname as first_name"
    .", person.lastname as last_name"
    .", person.middlename as middle_name"
	.", person.email as email"
	.", DATE_FORMAT(person.birthdate, '%m%d%y') as dob"
    .", address.phone, address1, address2, city, state, zip"
    .", orders.order_id as order_id"
    .", orders.creation_stamp as date_of_sale"
    .", promo_id"
    .", cc.b as card_type"
    .", cc.c as card_num"
    .", cc.d as card_exp"
    ." FROM orders"
    ." LEFT JOIN person ON orders.person_id = person.person_id "
    ." LEFT JOIN address ON orders.bill_addr = address.address_id "
    ." LEFT JOIN cc ON orders.order_id = cc.a "
    ." WHERE orders.status = 'APPROVED' "
	." AND orders.creation_stamp > '".$last_date."' "
	." AND orders.creation_stamp <= '".$file_date."' "
    ." AND promo_id != '99999' "
	." AND cc.c NOT LIKE '%1111111111111111%' "
    ." AND NOT (person.firstname = 'foobar' AND person.lastname = 'cool')"
    ." AND NOT (person.firstname = 'joe' AND person.lastname = 'cool')"
    ." AND NOT (person.firstname = 'test' OR person.lastname = 'test')"
    ." AND NOT (person.firstname = 'testmonkey' OR person.lastname = 'testmonkey')"
    ." AND NOT (person.firstname = 'testing' OR person.lastname = 'testing')"
	." GROUP BY person.email"
;

$result = $sql->Query ('criticschoicemembership_com', $query);
Error_2::Error_Test ($result, TRUE);

$result_count = $sql->Row_Count ($result);
echo $promo->site_code." has $result_count rows\n";

while ($row = $sql->Fetch_Array_Row($result))
{
	$bl[$bl_count]->order_id = $row['order_id'];
	$bl[$bl_count]->promo_id = $row['promo_id'];
	$bl[$bl_count]->first_name = $row['first_name'];
	$bl[$bl_count]->last_name = $row['last_name'];
	$bl[$bl_count]->middle_name = $row['middle_name'];
	$bl[$bl_count]->email = $row['email'];
	$bl[$bl_count]->dob = $row['dob'];
	$bl[$bl_count]->phone = $row['phone'];
	$bl[$bl_count]->address1 = $row['address1'];
	$bl[$bl_count]->address2 = $row['address2'];
	$bl[$bl_count]->city = $row['city'];
	$bl[$bl_count]->state = $row['state'];
	$bl[$bl_count]->zip = $row['zip'];
	$bl[$bl_count]->date_of_sale = $row['date_of_sale'];
	$bl[$bl_count]->card_num = $row['card_num'];
	$bl[$bl_count]->card_type = $row['card_type'];
	$bl[$bl_count]->card_exp = $row['card_exp'];
	$bl[$bl_count]->promo_code = $promo->promo_code;
	$bl[$bl_count]->site_code = $promo->site_code;

	$bl_count++;
}


// IdentityTracking
/*
$promo->site_code = "geitrack";
$promo->promo_code = "01004280";

$result = $sql->Query ('identitytracking_com', $query);
Error_2::Error_Test ($result, TRUE);

$result_count = $sql->Row_Count($result);
echo $promo->site_code." has $result_count rows\n";

while ($row = $sql->Fetch_Array_Row($result))
{
	$bl[$bl_count]->order_id = $row['order_id'];
	$bl[$bl_count]->promo_id = $row['promo_id'];
	$bl[$bl_count]->first_name = $row['first_name'];
	$bl[$bl_count]->last_name = $row['last_name'];
	$bl[$bl_count]->middle_name = $row['middle_name'];
	$bl[$bl_count]->email = $row['email'];
	$bl[$bl_count]->dob = $row['dob'];
	$bl[$bl_count]->phone = $row['phone'];
	$bl[$bl_count]->address1 = $row['address1'];
	$bl[$bl_count]->address2 = $row['address2'];
	$bl[$bl_count]->city = $row['city'];
	$bl[$bl_count]->state = $row['state'];
	$bl[$bl_count]->zip = $row['zip'];
	$bl[$bl_count]->date_of_sale = $row['date_of_sale'];
	$bl[$bl_count]->card_num = $row['card_num'];
	$bl[$bl_count]->card_type = $row['card_type'];
	$bl[$bl_count]->card_exp = $row['card_exp'];
	$bl[$bl_count]->promo_code = $promo->promo_code;
	$bl[$bl_count]->site_code = $promo->site_code;

	$bl_count++;
}
*/


// PerfectGetaway
$promo->site_code = "gepgmem";
$promo->promo_code = "01004215";

$result = $sql->Query ('perfectgetawaymembership_com', $query);
Error_2::Error_Test ($result, TRUE);

$result_count = $sql->Row_Count($result);
echo $promo->site_code." has $result_count rows\n";

while ($row = $sql->Fetch_Array_Row($result))
{
	$bl[$bl_count]->order_id = $row['order_id'];
	$bl[$bl_count]->promo_id = $row['promo_id'];
	$bl[$bl_count]->first_name = $row['first_name'];
	$bl[$bl_count]->last_name = $row['last_name'];
	$bl[$bl_count]->middle_name = $row['middle_name'];
	$bl[$bl_count]->email = $row['email'];
	$bl[$bl_count]->dob = $row['dob'];
	$bl[$bl_count]->phone = $row['phone'];
	$bl[$bl_count]->address1 = $row['address1'];
	$bl[$bl_count]->address2 = $row['address2'];
	$bl[$bl_count]->city = $row['city'];
	$bl[$bl_count]->state = $row['state'];
	$bl[$bl_count]->zip = $row['zip'];
	$bl[$bl_count]->date_of_sale = $row['date_of_sale'];
	$bl[$bl_count]->card_num = $row['card_num'];
	$bl[$bl_count]->card_type = $row['card_type'];
	$bl[$bl_count]->card_exp = $row['card_exp'];
	$bl[$bl_count]->promo_code = $promo->promo_code;
	$bl[$bl_count]->site_code = $promo->site_code;

	$bl_count++;
}

$new_batch = new GE_Batch ();
$batch_body = $new_batch->Make_Batch ($batch_id, $bl);


$query = "insert into file_out (file_id, file_date, file_body) values (".$batch_id.", '".$file_date."', '".mysql_escape_string($batch_body)."')";
Error_2::Error_Test ($sql->Query ('ge_batch', $query), TRUE);


$fn = 'SSO_clubenrolls_'.str_pad($batch_id, 4, '0', STR_PAD_LEFT).'.txt';
$fp = fopen ($fn, 'w');
fwrite ($fp, $batch_body);
fclose ($fp);


exec ("gpg -e --homedir /home/release/.gnupg --compress-algo 1 --cipher-algo cast5 --no-secmem-warning -r Electronic --always-trust --batch ".$fn);

if (! is_file ($fn.".gpg"))
{
	echo "ERROR: encrypted file $fn.gpg not found! I just can't go on!\n";
	exit;
}

exec ("mv $fn.gpg $fn.pgp");


$ftp = ftp_connect ('ftp.gepmg.com');
$res = ftp_login ($ftp, 'ssourpmg', 'iTI994a2');

ftp_pasv ($ftp, TRUE);

$res = ftp_chdir ($ftp, 'inbound');
if (! $res)
{
	echo "ERROR: unable to chdir to inbound\n";
	exit;
}

$res = ftp_put ($ftp, "$fn.pgp", "$fn.pgp", FTP_BINARY);
if (! $res)
{
	echo "ERROR: unable to put file\n";
	exit;
}

ftp_close ($ftp);


mail ('cd.esp@ge.com', $fn.' '.count($bl).' enrollments', '', 'From: john.hawkins@thesellingsource.com');
mail ('rodricg@sellingsource.com', $fn.' '.count($bl).' enrollments', '', 'From: john.hawkins@thesellingsource.com');
mail ('john.hawkins@thesellingsource.com', $fn.' '.count($bl).' enrollments', '', 'From: john.hawkins@thesellingsource.com');


?>